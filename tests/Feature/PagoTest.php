<?php

namespace Tests\Feature;

use App\Models\Mesa;
use App\Models\Orden;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_cobrar_orden_mantiene_mesa_ocupada_si_quedan_otras_comandas_activas(): void
    {
        $mesero = User::where('email', 'mesero@restaurante.com')->first();
        $token = $mesero->createToken('test')->plainTextToken;

        $mesa = Mesa::first();
        $mesa->update(['estado' => 'ocupada']);

        // Comanda 1
        $orden1 = Orden::create([
            'mesa_id' => $mesa->id,
            'user_id' => $mesero->id,
            'estado' => 'entregado',
            'total' => 100.00,
        ]);

        // Comanda 2 (pendiente de cobro futuro)
        $orden2 = Orden::create([
            'mesa_id' => $mesa->id,
            'user_id' => $mesero->id,
            'estado' => 'entregado',
            'total' => 150.00,
        ]);

        // Cobrar comanda 1
        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/pagos', [
                'orden_id' => $orden1->id,
                'metodo' => 'tarjeta',
            ]);

        $res->assertStatus(201);

        // La comanda 1 debe estar cerrada
        $this->assertEquals('cerrada', $orden1->fresh()->estado);

        // LA MESA DEBE SEGUIR OCUPADA porque aún le queda la comanda 2
        $this->assertEquals('ocupada', $mesa->fresh()->estado);

        // Ahora cobrar comanda 2
        $res2 = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/pagos', [
                'orden_id' => $orden2->id,
                'metodo' => 'efectivo',
            ]);

        $res2->assertStatus(201);

        // Ya no quedan más órdenes activas, la mesa AHORA SÍ debe ser disponible
        $this->assertEquals('disponible', $mesa->fresh()->estado);
    }

    public function test_re_cobro_de_orden_ya_cerrada_responde_200_con_idempotencia(): void
    {
        $mesero = User::where('email', 'mesero@restaurante.com')->first();
        $token = $mesero->createToken('test')->plainTextToken;

        $mesa = Mesa::first();
        $orden = Orden::create([
            'mesa_id' => $mesa->id,
            'user_id' => $mesero->id,
            'estado' => 'entregado',
            'total' => 100.00,
        ]);

        // Primer cobro
        $res1 = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/pagos', [
                'orden_id' => $orden->id,
                'metodo' => 'Efectivo',
            ]);
        $res1->assertStatus(201);

        // Reintento de cobro (el cliente hace clic de nuevo)
        $res2 = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/pagos', [
                'orden_id' => $orden->id,
                'metodo' => 'Efectivo',
            ]);

        // Responde 200 con mensaje de éxito/idempotencia en lugar de 422 para no trabar la UI móvil
        $res2->assertStatus(200)
            ->assertJsonPath('mensaje', 'Esta orden ya fue cobrada y cerrada exitosamente.');
    }

    public function test_permite_cobrar_cuenta_total_por_mesa_id(): void
    {
        $mesero = User::where('email', 'mesero@restaurante.com')->first();
        $token = $mesero->createToken('test')->plainTextToken;

        $mesa = Mesa::first();
        $mesa->update(['estado' => 'ocupada']);

        $orden = Orden::create([
            'mesa_id' => $mesa->id,
            'user_id' => $mesero->id,
            'estado' => 'entregado',
            'total' => 160.00,
        ]);

        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/pagos', [
                'mesa_id' => $mesa->id,
                'metodo' => 'Efectivo',
            ]);

        $res->assertStatus(201)
            ->assertJsonPath('mensaje', 'Pago registrado correctamente');

        $this->assertEquals('cerrada', $orden->fresh()->estado);
        $this->assertEquals('disponible', $mesa->fresh()->estado);
    }
}
