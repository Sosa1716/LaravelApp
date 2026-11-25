<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Mesa;
use App\Models\Platillo;
use App\Models\Orden;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_solo_administrador_puede_gestionar_usuarios(): void
    {
        // 1. Un mesero no puede consultar ni crear usuarios
        $mesero = User::where('email', 'mesero@restaurante.com')->first();
        $meseroToken = $mesero->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$meseroToken}")
            ->getJson('/api/usuarios');

        $response->assertStatus(403);

        // 2. Un administrador sí puede consultar
        $admin = User::where('email', 'admin@restaurante.com')->first();
        $adminToken = $admin->createToken('test')->plainTextToken;

        $responseAdmin = $this->withHeader('Authorization', "Bearer {$adminToken}")
            ->getJson('/api/usuarios');

        $responseAdmin->assertStatus(200)
            ->assertJsonStructure([
                '*' => ['id', 'name', 'email', 'role_id', 'role']
            ]);
    }

    public function test_administrador_crea_mesero_y_este_inicia_sesion_y_atiende_orden(): void
    {
        $admin = User::where('email', 'admin@restaurante.com')->first();
        $adminToken = $admin->createToken('test')->plainTextToken;
        $rolMesero = Role::where('nombre', 'Mesero')->first();

        // 1. Crear nuevo mesero desde el CRUD de administración
        $createResponse = $this->withHeader('Authorization', "Bearer {$adminToken}")
            ->postJson('/api/usuarios', [
                'name' => 'Carlos Mesero Test',
                'email' => 'carlos.mesero@restaurante.com',
                'password' => 'password123',
                'role_id' => $rolMesero->id,
            ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('mensaje', 'Usuario registrado correctamente')
            ->assertJsonPath('usuario.name', 'Carlos Mesero Test')
            ->assertJsonPath('usuario.email', 'carlos.mesero@restaurante.com')
            ->assertJsonPath('usuario.role.nombre', 'Mesero');

        $nuevoMeseroId = $createResponse->json('usuario.id');

        // 2. El nuevo mesero inicia sesión en la app mediante /api/login
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'carlos.mesero@restaurante.com',
            'password' => 'password123',
        ]);

        $loginResponse->assertStatus(200)
            ->assertJsonStructure(['token', 'usuario'])
            ->assertJsonPath('usuario.nombre', 'Carlos Mesero Test')
            ->assertJsonPath('usuario.rol', 'Mesero');

        $tokenMesero = $loginResponse->json('token');

        // 3. El nuevo mesero registra una comanda desde la app
        $mesa = Mesa::where('estado', 'disponible')->first();
        $platillo = Platillo::where('disponible', true)->first();

        $ordenResponse = $this->withHeader('Authorization', "Bearer {$tokenMesero}")
            ->postJson('/api/ordenes', [
                'mesa_id' => $mesa->id,
                'items' => [
                    [
                        'platillo_id' => $platillo->id,
                        'cantidad' => 1,
                        'notas' => 'Atendido por Carlos'
                    ]
                ]
            ]);

        $ordenResponse->assertStatus(201);
        $ordenId = $ordenResponse->json('orden.id');

        // 4. Se comprueba que la orden registra y muestra que el mesero Carlos la atendió
        $consultaResponse = $this->withHeader('Authorization', "Bearer {$adminToken}")
            ->getJson('/api/ordenes');

        $consultaResponse->assertStatus(200);
        $ordenRegistrada = collect($consultaResponse->json())->firstWhere('id', $ordenId);

        $this->assertNotNull($ordenRegistrada);
        $this->assertEquals('Carlos Mesero Test', $ordenRegistrada['user']['name']);
        $this->assertEquals($nuevoMeseroId, $ordenRegistrada['user']['id']);

        // 5. Editar mesero
        $updateResponse = $this->withHeader('Authorization', "Bearer {$adminToken}")
            ->putJson("/api/usuarios/{$nuevoMeseroId}", [
                'name' => 'Carlos Mesero Actualizado',
                'email' => 'carlos.mesero@restaurante.com',
                'role_id' => $rolMesero->id,
            ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('usuario.name', 'Carlos Mesero Actualizado');
    }

    public function test_administrador_no_puede_eliminar_su_propia_cuenta(): void
    {
        $admin = User::where('email', 'admin@restaurante.com')->first();
        $adminToken = $admin->createToken('test')->plainTextToken;

        $deleteResponse = $this->withHeader('Authorization', "Bearer {$adminToken}")
            ->deleteJson("/api/usuarios/{$admin->id}");

        $deleteResponse->assertStatus(422)
            ->assertJsonPath('mensaje', 'No puedes eliminar tu propia cuenta en sesión.');
    }
}

