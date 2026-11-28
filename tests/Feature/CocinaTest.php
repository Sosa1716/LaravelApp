<?php

namespace Tests\Feature;

use App\Models\Mesa;
use App\Models\Orden;
use App\Models\OrdenDetalle;
use App\Models\Platillo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CocinaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_login_con_usuario_rol_cocina(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'cocina@restaurante.com',
            'password' => '12345678',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'mensaje',
                'token',
                'usuario' => ['id', 'nombre', 'email', 'rol'],
            ])
            ->assertJsonPath('usuario.rol', 'Cocina');
    }

    public function test_cocina_puede_ver_ordenes_activas(): void
    {
        $userCocina = User::where('email', 'cocina@restaurante.com')->first();

        $response = $this->actingAs($userCocina, 'sanctum')
            ->getJson('/api/ordenes');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'mesa_id',
                    'estado',
                    'total',
                    'mesa',
                    'detalles' => [
                        '*' => ['id', 'platillo_id', 'cantidad', 'notas', 'platillo'],
                    ],
                ],
            ]);
    }

    public function test_cocina_puede_cambiar_orden_a_preparando_y_luego_a_listo(): void
    {
        $userCocina = User::where('email', 'cocina@restaurante.com')->first();
        $orden = Orden::where('estado', 'pendiente')->first();

        // Cambiar a preparando
        $response1 = $this->actingAs($userCocina, 'sanctum')
            ->patchJson("/api/ordenes/{$orden->id}/estado", [
                'estado' => 'preparando',
            ]);

        $response1->assertStatus(200)
            ->assertJsonPath('orden.estado', 'preparando');

        // Cambiar a listo
        $response2 = $this->actingAs($userCocina, 'sanctum')
            ->patchJson("/api/ordenes/{$orden->id}/estado", [
                'estado' => 'listo',
            ]);

        $response2->assertStatus(200)
            ->assertJsonPath('orden.estado', 'listo');
    }

    public function test_cocina_no_puede_administrar_platillos_o_pagos(): void
    {
        $userCocina = User::where('email', 'cocina@restaurante.com')->first();

        // Intentar crear un platillo
        $responsePlatillo = $this->actingAs($userCocina, 'sanctum')
            ->postJson('/api/platillos', [
                'nombre' => 'Nuevo Platillo',
                'precio' => 100,
                'categoria_id' => 1,
            ]);

        $responsePlatillo->assertStatus(403);

        // Intentar registrar un pago
        $responsePago = $this->actingAs($userCocina, 'sanctum')
            ->postJson('/api/pagos', [
                'orden_id' => 1,
                'metodo' => 'efectivo',
                'monto' => 100,
            ]);

        $responsePago->assertStatus(403);
    }

    public function test_cocina_puede_marcar_platillos_individualmente_y_sincronizar_orden(): void
    {
        $userCocina = User::where('email', 'cocina@restaurante.com')->first();
        $orden = Orden::where('estado', 'pendiente')->with('detalles')->first();
        $this->assertGreaterThanOrEqual(2, $orden->detalles->count());

        $detalle1 = $orden->detalles[0];
        $detalle2 = $orden->detalles[1];

        // Marcar primer platillo en preparación -> orden pasa a preparando
        $res1 = $this->actingAs($userCocina, 'sanctum')
            ->patchJson("/api/orden-detalles/{$detalle1->id}/estado", [
                'estado' => 'preparando',
            ]);

        $res1->assertStatus(200)
            ->assertJsonPath('detalle.estado', 'preparando')
            ->assertJsonPath('orden.estado', 'preparando');

        // Marcar primer platillo listo, segundo sigue pendiente -> orden sigue en preparando
        $res2 = $this->actingAs($userCocina, 'sanctum')
            ->patchJson("/api/orden-detalles/{$detalle1->id}/estado", [
                'estado' => 'listo',
            ]);

        $res2->assertStatus(200)
            ->assertJsonPath('detalle.estado', 'listo')
            ->assertJsonPath('orden.estado', 'preparando');

        // Marcar segundo platillo en preparación y luego listo -> orden pasa a listo
        $this->actingAs($userCocina, 'sanctum')
            ->patchJson("/api/orden-detalles/{$detalle2->id}/estado", [
                'estado' => 'preparando',
            ])->assertStatus(200);

        $res3 = $this->actingAs($userCocina, 'sanctum')
            ->patchJson("/api/orden-detalles/{$detalle2->id}/estado", [
                'estado' => 'listo',
            ]);

        $res3->assertStatus(200)
            ->assertJsonPath('detalle.estado', 'listo')
            ->assertJsonPath('orden.estado', 'listo');
    }

    public function test_no_permite_entregar_mesa_mientras_queden_platillos_pendientes_o_en_preparacion(): void
    {
        $userCocina = User::where('email', 'cocina@restaurante.com')->first();
        $orden = Orden::where('estado', 'pendiente')->with('detalles')->first();
        $mesaId = $orden->mesa_id;

        // Marcar solo el primer platillo como listo
        $this->actingAs($userCocina, 'sanctum')
            ->patchJson("/api/orden-detalles/{$orden->detalles[0]->id}/estado", [
                'estado' => 'listo',
            ])->assertStatus(200);

        // Intentar entregar mesa mientras queda al menos un platillo pendiente
        $resEntrega = $this->actingAs($userCocina, 'sanctum')
            ->postJson("/api/mesas/{$mesaId}/entregar");

        $resEntrega->assertStatus(422)
            ->assertJsonPath('mensaje', 'No se puede entregar la mesa mientras haya platillos pendientes o en preparación.');
    }

    public function test_entregar_mesa_con_4_comensales_notifica_al_mesero_sin_duplicados(): void
    {
        $userCocina = User::where('email', 'cocina@restaurante.com')->first();
        $mesero = User::where('email', 'mesero@restaurante.com')->first();
        $mesa = Mesa::create(['numero' => 99, 'estado' => 'ocupada']);
        $platillos = Platillo::where('disponible', true)->take(4)->get();

        // Crear una mesa con 4 comensales (4 platillos en la mesa)
        $orden = Orden::create([
            'mesa_id' => $mesa->id,
            'user_id' => $mesero->id,
            'estado' => 'pendiente',
            'total' => 400,
        ]);

        $detalles = [];
        for ($i = 0; $i < 4; $i++) {
            $platillo = $platillos[$i % $platillos->count()];
            $detalles[] = OrdenDetalle::create([
                'orden_id' => $orden->id,
                'platillo_id' => $platillo->id,
                'cantidad' => 1,
                'precio' => $platillo->precio,
                'estado' => 'pendiente',
            ]);
        }

        // Marcar los 4 platillos como preparando y luego listos
        foreach ($detalles as $detalle) {
            $this->actingAs($userCocina, 'sanctum')
                ->patchJson("/api/orden-detalles/{$detalle->id}/estado", ['estado' => 'preparando'])
                ->assertStatus(200);

            $this->actingAs($userCocina, 'sanctum')
                ->patchJson("/api/orden-detalles/{$detalle->id}/estado", ['estado' => 'listo'])
                ->assertStatus(200);
        }

        // Entregar mesa
        $resEntrega = $this->actingAs($userCocina, 'sanctum')
            ->postJson("/api/mesas/{$mesa->id}/entregar");

        $resEntrega->assertStatus(200)
            ->assertJsonPath('notificacion_enviada', true)
            ->assertJsonPath('notificados', 1);

        // Verificar que la orden y sus 4 platillos quedaron como entregados
        $this->assertDatabaseHas('ordenes', [
            'id' => $orden->id,
            'estado' => 'entregado',
        ]);
        foreach ($detalles as $detalle) {
            $this->assertDatabaseHas('orden_detalles', [
                'id' => $detalle->id,
                'estado' => 'entregado',
            ]);
        }

        // Reintentar entregar la misma mesa no genera notificación duplicada
        $resReintento = $this->actingAs($userCocina, 'sanctum')
            ->postJson("/api/mesas/{$mesa->id}/entregar");

        $resReintento->assertStatus(200)
            ->assertJsonPath('notificacion_enviada', false)
            ->assertJsonPath('notificados', 0);

        // El mesero consulta sus notificaciones y ve exactamente 1 sin duplicados
        $resNotif = $this->actingAs($mesero, 'sanctum')
            ->getJson('/api/notificaciones');

        $resNotif->assertStatus(200)
            ->assertJsonPath('unread_count', 1);

        $notificaciones = $resNotif->json('notificaciones');
        $this->assertCount(1, $notificaciones);
        $this->assertEquals($mesa->id, $notificaciones[0]['data']['mesa_id']);

        // Marcar notificación como leída
        $notifId = $notificaciones[0]['id'];
        $this->actingAs($mesero, 'sanctum')
            ->patchJson("/api/notificaciones/{$notifId}/leer")
            ->assertStatus(200)
            ->assertJsonPath('unread_count', 0);
    }
}
