<?php

namespace Tests\Unit;

use App\Http\Middleware\RoleMiddleware;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    private function requestCon(?User $user): Request
    {
        $request = Request::create('/api/recurso', 'GET');
        $request->setUserResolver(fn () => $user);

        return $request;
    }

    private function usuarioConRol(string $rol): User
    {
        $user = new User;
        $user->setRelation('role', new Role(['nombre' => $rol]));

        return $user;
    }

    public function test_rechaza_con_401_si_no_hay_usuario_autenticado(): void
    {
        $response = (new RoleMiddleware)->handle(
            $this->requestCon(null),
            fn () => new Response('ok'),
            'Administrador'
        );

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_permite_el_paso_si_el_rol_esta_autorizado(): void
    {
        $response = (new RoleMiddleware)->handle(
            $this->requestCon($this->usuarioConRol('Mesero')),
            fn () => new Response('ok'),
            'Administrador', 'Mesero'
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_rechaza_con_403_si_el_rol_no_esta_autorizado(): void
    {
        $response = (new RoleMiddleware)->handle(
            $this->requestCon($this->usuarioConRol('Cocina')),
            fn () => new Response('ok'),
            'Administrador'
        );

        $this->assertSame(403, $response->getStatusCode());
    }
}
