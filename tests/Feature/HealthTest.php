<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Endpoints que usa el pipeline de CI/CD para verificar un despliegue.
 */
class HealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_endpoint_de_salud_responde(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_endpoint_de_version_expone_el_commit_desplegado(): void
    {
        config(['app.version' => 'abc1234']);

        $this->getJson('/api/version')
            ->assertOk()
            ->assertJsonStructure(['app', 'env', 'version'])
            ->assertJsonPath('version', 'abc1234');
    }

    public function test_login_con_credenciales_invalidas_no_rompe_la_api(): void
    {
        // El smoke test del deploy usa esta misma petición: si la BD no
        // respondiera, devolvería 500 en lugar de un error de validación/auth.
        $response = $this->postJson('/api/login', [
            'email' => 'no-existe@example.com',
            'password' => 'incorrecta',
        ]);

        $this->assertContains($response->status(), [401, 422]);
    }
}
