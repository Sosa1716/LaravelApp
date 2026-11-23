<?php

namespace Tests\Feature;

use App\Models\Mesa;
use App\Models\Platillo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    public function test_orden_enviada_desde_app_movil_se_registra_y_se_consulta_en_web(): void
    {
        // 1. Simular login de la app móvil (Mesero) que obtiene Bearer Token
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'mesero@restaurante.com',
            'password' => '12345678',
        ], [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ]);

        $loginResponse->assertStatus(200)
            ->assertJsonStructure(['token', 'usuario']);

        $token = $loginResponse->json('token');
        $mesa = Mesa::where('estado', 'disponible')->first();
        $platillo = Platillo::where('disponible', true)->first();

        // 2. Mesero crea comanda desde la app móvil con encabezados protegidos
        $ordenResponse = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->postJson('/api/ordenes', [
            'mesa_id' => $mesa->id,
            'items' => [
                [
                    'platillo_id' => $platillo->id,
                    'cantidad' => 2,
                    'notas' => 'Pedido enviado desde celular',
                ],
            ],
        ]);

        $ordenResponse->assertStatus(201)
            ->assertJsonPath('mensaje', 'Orden registrada correctamente');

        $ordenId = $ordenResponse->json('orden.id');

        // 3. Mesa pasa a ocupada automáticamente
        $this->assertDatabaseHas('mesas', [
            'id' => $mesa->id,
            'estado' => 'ocupada',
        ]);

        // 4. La Cocina (desde la web con su Bearer Token) consulta las órdenes y la comanda aparece
        $userCocina = User::where('email', 'cocina@restaurante.com')->first();
        $cocinaLogin = $this->postJson('/api/login', [
            'email' => 'cocina@restaurante.com',
            'password' => '12345678',
        ]);
        $cocinaToken = $cocinaLogin->json('token');

        $cocinaResponse = $this->withHeaders([
            'Authorization' => "Bearer {$cocinaToken}",
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->getJson('/api/ordenes');

        $cocinaResponse->assertStatus(200);
        $ordenEncontrada = collect($cocinaResponse->json())->firstWhere('id', $ordenId);
        $this->assertNotNull($ordenEncontrada);
        $this->assertEquals('pendiente', $ordenEncontrada['estado']);
        $this->assertEquals('Pedido enviado desde celular', $ordenEncontrada['detalles'][0]['notas']);

        // 5. El Administrador (desde la web con su Bearer Token) también la consulta en su historial
        $adminLogin = $this->postJson('/api/login', [
            'email' => 'admin@restaurante.com',
            'password' => '12345678',
        ]);
        $adminToken = $adminLogin->json('token');

        $adminResponse = $this->withHeaders([
            'Authorization' => "Bearer {$adminToken}",
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->getJson('/api/ordenes');

        $adminResponse->assertStatus(200);
        $this->assertNotNull(collect($adminResponse->json())->firstWhere('id', $ordenId));
    }
}
