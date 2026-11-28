<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\MesaController;
use App\Http\Controllers\Api\NotificacionController;
use App\Http\Controllers\Api\OrdenController;
use App\Http\Controllers\Api\PagoController;
use App\Http\Controllers\Api\PlatilloController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

// Público: usado por el pipeline de CI/CD para verificar qué commit está desplegado.
Route::get('/version', fn () => response()->json([
    'app' => config('app.name'),
    'env' => config('app.env'),
    'version' => config('app.version'),
    'booted_at' => config('app.booted_at'),
]));

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // CONSULTAS GENERALES
    Route::get('/platillos', [PlatilloController::class, 'index']);
    Route::get('/platillos/{platillo}', [PlatilloController::class, 'show']);

    Route::get('/categorias', [CategoriaController::class, 'index']);
    Route::get('/categorias/{categoria}', [CategoriaController::class, 'show']);

    Route::get('/mesas', [MesaController::class, 'index']);
    Route::get('/mesas/{mesa}', [MesaController::class, 'show']);

    // ADMINISTRADOR
    Route::middleware('role:Administrador')->group(function () {

        Route::post('/platillos', [PlatilloController::class, 'store']);
        Route::put('/platillos/{platillo}', [PlatilloController::class, 'update']);
        Route::delete('/platillos/{platillo}', [PlatilloController::class, 'destroy']);

        Route::post('/categorias', [CategoriaController::class, 'store']);
        Route::put('/categorias/{categoria}', [CategoriaController::class, 'update']);
        Route::delete('/categorias/{categoria}', [CategoriaController::class, 'destroy']);

        Route::post('/mesas', [MesaController::class, 'store']);
        Route::put('/mesas/{mesa}', [MesaController::class, 'update']);
        Route::delete('/mesas/{mesa}', [MesaController::class, 'destroy']);

        Route::get('/usuarios', [UserController::class, 'index']);
        Route::post('/usuarios', [UserController::class, 'store']);
        Route::get('/usuarios/{usuario}', [UserController::class, 'show']);
        Route::put('/usuarios/{usuario}', [UserController::class, 'update']);
        Route::delete('/usuarios/{usuario}', [UserController::class, 'destroy']);
        Route::get('/roles', [UserController::class, 'roles']);
    });

    // ÓRDENES
    Route::get('/ordenes', [OrdenController::class, 'index'])
        ->middleware('role:Administrador,Mesero,Cocina');

    Route::get('/ordenes/{orden}', [OrdenController::class, 'show'])
        ->middleware('role:Administrador,Mesero,Cocina');

    Route::post('/ordenes', [OrdenController::class, 'store'])
        ->middleware('role:Administrador,Mesero');

    Route::patch('/ordenes/{orden}/estado', [OrdenController::class, 'updateEstado'])
        ->middleware('role:Administrador,Mesero,Cocina');

    Route::patch('/orden-detalles/{ordenDetalle}/estado', [OrdenController::class, 'updateDetalleEstado'])
        ->middleware('role:Administrador,Cocina');

    Route::post('/mesas/{mesa}/entregar', [OrdenController::class, 'entregarMesa'])
        ->middleware('role:Administrador,Cocina');

    // NOTIFICACIONES
    Route::get('/notificaciones', [NotificacionController::class, 'index']);
    Route::patch('/notificaciones/{id}/leer', [NotificacionController::class, 'marcarLeida']);
    Route::post('/notificaciones/leer-todas', [NotificacionController::class, 'marcarTodasLeidas']);

    // PAGOS
    Route::get('/pagos', [PagoController::class, 'index'])
        ->middleware('role:Administrador,Mesero');

    Route::post('/pagos', [PagoController::class, 'store'])
        ->middleware('role:Administrador,Mesero');
});
