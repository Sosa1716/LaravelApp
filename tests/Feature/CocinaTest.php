<?php

namespace Tests\Feature;

use App\Models\Orden;
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
}
