<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Orden;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $usuarios = User::with('role')
            ->latest()
            ->get();

        return response()->json($usuarios);
    }

    public function roles()
    {
        $roles = Role::all();

        return response()->json($roles);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'role_id' => 'required|exists:roles,id',
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.unique' => 'Este correo ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'role_id.required' => 'El rol es obligatorio.',
            'role_id.exists' => 'El rol seleccionado no es válido.',
        ]);

        $usuario = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
        ]);

        return response()->json([
            'mensaje' => 'Usuario registrado correctamente',
            'usuario' => $usuario->load('role'),
        ], 201);
    }

    public function show(User $usuario)
    {
        return response()->json($usuario->load('role'));
    }

    public function update(Request $request, User $usuario)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($usuario->id),
            ],
            'password' => 'nullable|string|min:6',
            'role_id' => 'required|exists:roles,id',
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.unique' => 'Este correo ya está registrado.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'role_id.required' => 'El rol es obligatorio.',
            'role_id.exists' => 'El rol seleccionado no es válido.',
        ]);

        $datos = [
            'name' => $request->name,
            'email' => $request->email,
            'role_id' => $request->role_id,
        ];

        if ($request->filled('password')) {
            $datos['password'] = Hash::make($request->password);
        }

        $usuario->update($datos);

        return response()->json([
            'mensaje' => 'Usuario actualizado correctamente',
            'usuario' => $usuario->load('role'),
        ]);
    }

    public function destroy(Request $request, User $usuario)
    {
        if ($usuario->id === $request->user()->id) {
            return response()->json([
                'mensaje' => 'No puedes eliminar tu propia cuenta en sesión.',
            ], 422);
        }

        if (Orden::where('user_id', $usuario->id)->exists()) {
            return response()->json([
                'mensaje' => 'No se puede eliminar este usuario porque tiene comandas u órdenes registradas en el historial. Puedes editar sus datos o contraseña.',
            ], 422);
        }

        $usuario->tokens()->delete();
        $usuario->delete();

        return response()->json([
            'mensaje' => 'Usuario eliminado correctamente',
        ]);
    }
}
