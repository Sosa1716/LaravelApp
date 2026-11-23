<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Mesa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_admin_puede_iniciar_sesion(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'admin@restaurante.com',
            'password' => '12345678',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('usuario.rol', 'Administrador');
    }

    public function test_admin_puede_administrar_categorias(): void
    {
        $admin = User::where('email', 'admin@restaurante.com')->first();

        // 1. Crear
        $create = $this->actingAs($admin, 'sanctum')->postJson('/api/categorias', [
            'nombre' => 'Entradas',
            'descripcion' => 'Aperitivos y botanas',
            'activo' => true,
        ]);
        $create->assertStatus(201);
        $catId = $create->json('categoria.id');

        // 2. Editar
        $update = $this->actingAs($admin, 'sanctum')->putJson("/api/categorias/{$catId}", [
            'nombre' => 'Entradas Gourmet',
            'descripcion' => 'Aperitivos selectos',
            'activo' => true,
        ]);
        $update->assertStatus(200)
            ->assertJsonPath('categoria.nombre', 'Entradas Gourmet');

        // 3. Eliminar (sin platillos asociados)
        $delete = $this->actingAs($admin, 'sanctum')->deleteJson("/api/categorias/{$catId}");
        $delete->assertStatus(200);
    }

    public function test_admin_puede_administrar_platillos(): void
    {
        $admin = User::where('email', 'admin@restaurante.com')->first();
        $cat = Categoria::first();

        // 1. Crear
        $create = $this->actingAs($admin, 'sanctum')->postJson('/api/platillos', [
            'categoria_id' => $cat->id,
            'nombre' => 'Ensalada César',
            'descripcion' => 'Lechuga romana, crutones, parmesano',
            'precio' => 110.50,
            'imagen' => 'https://example.com/ensalada.jpg',
            'disponible' => true,
        ]);
        $create->assertStatus(201);
        $platilloId = $create->json('platillo.id');

        // 2. Editar
        $update = $this->actingAs($admin, 'sanctum')->putJson("/api/platillos/{$platilloId}", [
            'precio' => 125.00,
            'disponible' => false,
        ]);
        $update->assertStatus(200)
            ->assertJsonPath('platillo.precio', 125);

        // 3. Eliminar
        $delete = $this->actingAs($admin, 'sanctum')->deleteJson("/api/platillos/{$platilloId}");
        $delete->assertStatus(200);
    }

    public function test_admin_puede_administrar_mesas(): void
    {
        $admin = User::where('email', 'admin@restaurante.com')->first();

        // 1. Crear mesa
        $create = $this->actingAs($admin, 'sanctum')->postJson('/api/mesas', [
            'numero' => 99,
            'estado' => 'disponible',
        ]);
        $create->assertStatus(201);
        $mesaId = $create->json('mesa.id');

        // 2. Actualizar estado
        $update = $this->actingAs($admin, 'sanctum')->putJson("/api/mesas/{$mesaId}", [
            'estado' => 'reservada',
        ]);
        $update->assertStatus(200)
            ->assertJsonPath('mesa.estado', 'reservada');

        // 3. Eliminar
        $delete = $this->actingAs($admin, 'sanctum')->deleteJson("/api/mesas/{$mesaId}");
        $delete->assertStatus(200);
    }

    public function test_admin_puede_consultar_ordenes_y_pagos(): void
    {
        $admin = User::where('email', 'admin@restaurante.com')->first();

        $ordenes = $this->actingAs($admin, 'sanctum')->getJson('/api/ordenes');
        $ordenes->assertStatus(200);

        $pagos = $this->actingAs($admin, 'sanctum')->getJson('/api/pagos');
        $pagos->assertStatus(200);
    }
}
