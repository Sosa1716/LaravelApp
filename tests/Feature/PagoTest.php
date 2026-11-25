<?php

namespace Tests\Feature;

use App\Models\Mesa;
use App\Models\Orden;
use App\Models\Platillo;
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

    public function test_no_se_puede_cobrar_si_la_mesa_tiene_comandas_pendientes_en_cocina(): void
    {
        $mesero = User::where('email', 'mesero@restaurante.com')->first();
        $token = $mesero->createToken('test')->plainTextToken;

        $mesa = Mesa::first();
        $mesa->update(['estado' => 'ocupada']);
        $platillo = Platillo::first();

        // Comanda 1: entregada
        $ordenEntregada = Orden::create([
            'mesa_id' => $mesa->id,
            'user_id' => $mesero->id,
            'estado' => 'entregado',
            'total' => 160.00,
        ]);

        // Comanda 2: aún pendiente en cocina
        $ordenPendiente = Orden::create([
            'mesa_id' => $mesa->id,
            'user_id' => $mesero->id,
            'estado' => 'preparando',
            'total' => 80.00,
        ]);

        // Intentar cobrar la comanda entregada cuando la mesa aún tiene comida pendiente en cocina
        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/pagos', [
                'orden_id' => $ordenEntregada->id,
                'metodo' => 'Efectivo',
            ]);

        $res->assertStatus(422)
            ->assertJsonPath('mensaje', 'No se puede cobrar la mesa porque aún tiene comandas pendientes o en preparación en cocina.');
    }

    public function test_cobrar_orden_mantiene_mesa_ocupada_si_quedan_otras_comandas_activas(): void
    {
        $mesero = User::where('email', 'mesero@restaurante.com')->first();
        $token = $mesero->createToken('test')->plainTextToken;

        $mesa = Mesa::first();
        $mesa->update(['estado' => 'ocupada']);

        // Comanda 1: entregada
        $orden1 = Orden::create([
            'mesa_id' => $mesa->id,
            'user_id' => $mesero->id,
            'estado' => 'entregado',
            'total' => 100.00,
        ]);

        // Comanda 2: también entregada (pendiente de cobro futuro)
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

    public function test_rechaza_cobro_de_orden_ya_cerrada(): void
    {
        $mesero = User::where('email', 'mesero@restaurante.com')->first();
        $token = $mesero->createToken('test')->plainTextToken;

        $mesa = Mesa::first();
        $ordenCerrada = Orden::create([
            'mesa_id' => $mesa->id,
            'user_id' => $mesero->id,
            'estado' => 'cerrada',
            'total' => 100.00,
        ]);

        $res = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/pagos', [
                'orden_id' => $ordenCerrada->id,
                'metodo' => 'efectivo',
            ]);

        $res->assertStatus(422)
            ->assertJsonPath('mensaje', 'Esta orden ya fue cerrada y cobrada previamente.');
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
