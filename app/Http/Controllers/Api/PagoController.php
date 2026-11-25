<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mesa;
use App\Models\Orden;
use App\Models\Pago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PagoController extends Controller
{
    public function index()
    {
        $pagos = Pago::with([
            'orden.mesa',
        ])
            ->latest()
            ->get();

        return response()->json($pagos);
    }

    public function store(Request $request)
    {
        // Normalizar método de pago (insensible a mayúsculas y espacios)
        $metodoInput = strtolower(trim((string) $request->input('metodo', 'efectivo')));
        if (! in_array($metodoInput, ['efectivo', 'tarjeta'])) {
            return response()->json([
                'mensaje' => 'El método de pago debe ser efectivo o tarjeta.',
                'message' => 'El método de pago debe ser efectivo o tarjeta.',
                'errors' => ['metodo' => ['El método de pago debe ser efectivo o tarjeta.']],
            ], 422);
        }

        // Caso A: Cobro por mesa_id (cuando la app envía la mesa completa)
        if (! $request->filled('orden_id') && $request->filled('mesa_id')) {
            $mesaId = $request->input('mesa_id');
            $mesa = Mesa::find($mesaId);

            if (! $mesa) {
                return response()->json([
                    'mensaje' => 'La mesa indicada no existe.',
                    'message' => 'La mesa indicada no existe.',
                    'errors' => ['mesa_id' => ['La mesa indicada no existe.']],
                ], 422);
            }

            // Verificar si la mesa ya está liberada y sin órdenes activas
            $ordenesMesa = Orden::where('mesa_id', $mesaId)
                ->whereIn('estado', ['pendiente', 'preparando', 'listo', 'entregado'])
                ->get();

            if ($ordenesMesa->isEmpty()) {
                return response()->json([
                    'mensaje' => 'Esta mesa ya fue cerrada o no tiene comandas pendientes de cobro.',
                    'message' => 'Esta mesa ya fue cerrada o no tiene comandas pendientes de cobro.',
                    'errors' => ['mesa_id' => ['Esta mesa ya fue cerrada o no tiene comandas pendientes de cobro.']],
                ], 422);
            }

            // Verificar si hay comandas pendientes en cocina
            $pendientesCocina = $ordenesMesa->whereIn('estado', ['pendiente', 'preparando', 'listo']);
            if ($pendientesCocina->isNotEmpty()) {
                return response()->json([
                    'mensaje' => 'No se puede cobrar la mesa porque aún tiene comandas pendientes o en preparación en cocina.',
                    'message' => 'No se puede cobrar la mesa porque aún tiene comandas pendientes o en preparación en cocina.',
                    'errors' => ['mesa_id' => ['No se puede cobrar la mesa porque aún tiene comandas pendientes o en preparación en cocina.']],
                ], 422);
            }

            return DB::transaction(function () use ($ordenesMesa, $mesa, $metodoInput) {
                $pagos = [];
                foreach ($ordenesMesa as $o) {
                    $p = Pago::create([
                        'orden_id' => $o->id,
                        'metodo' => $metodoInput,
                        'monto' => $o->total,
                        'estado' => 'pagado',
                    ]);
                    $o->update(['estado' => 'cerrada']);
                    $pagos[] = $p;
                }

                $mesa->update(['estado' => 'disponible']);

                $ultimoPago = end($pagos);

                return response()->json([
                    'mensaje' => 'Pago registrado correctamente',
                    'message' => 'Pago registrado correctamente',
                    'pagos' => $pagos,
                    'pago' => $ultimoPago ? $ultimoPago->load('orden.mesa') : null,
                ], 201);
            });
        }

        // Caso B: Cobro por orden_id
        if (! $request->filled('orden_id')) {
            return response()->json([
                'mensaje' => 'Debes indicar el número de orden (orden_id) o mesa (mesa_id).',
                'message' => 'Debes indicar el número de orden (orden_id) o mesa (mesa_id).',
                'errors' => ['orden_id' => ['El campo orden_id o mesa_id es obligatorio.']],
            ], 422);
        }

        $ordenId = $request->input('orden_id');

        return DB::transaction(function () use ($ordenId, $metodoInput) {
            $orden = Orden::lockForUpdate()->find($ordenId);

            if (! $orden) {
                return response()->json([
                    'mensaje' => 'La comanda solicitada no existe.',
                    'message' => 'La comanda solicitada no existe.',
                    'errors' => ['orden_id' => ['La comanda solicitada no existe.']],
                ], 422);
            }

            // 1. Si la orden ya está cerrada
            if ($orden->estado === 'cerrada') {
                return response()->json([
                    'mensaje' => 'Esta orden ya fue cerrada y cobrada previamente.',
                    'message' => 'Esta orden ya fue cerrada y cobrada previamente.',
                    'errors' => ['orden_id' => ['Esta orden ya fue cerrada y cobrada previamente.']],
                ], 422);
            }

            // 2. Si la orden no ha sido entregada aún
            if ($orden->estado !== 'entregado') {
                return response()->json([
                    'mensaje' => 'La orden debe estar en estado entregada antes de cobrarla.',
                    'message' => 'La orden debe estar en estado entregada antes de cobrarla.',
                    'errors' => ['orden_id' => ['La orden debe estar entregada antes de realizar el pago.']],
                ], 422);
            }

            // 3. REGLA CLAVE: Verificar si la mesa tiene comandas pendientes en cocina
            $comandasPendientesEnCocina = Orden::where('mesa_id', $orden->mesa_id)
                ->where('id', '!=', $orden->id)
                ->whereIn('estado', ['pendiente', 'preparando', 'listo'])
                ->exists();

            if ($comandasPendientesEnCocina) {
                return response()->json([
                    'mensaje' => 'No se puede cobrar la mesa porque aún tiene comandas pendientes o en preparación en cocina.',
                    'message' => 'No se puede cobrar la mesa porque aún tiene comandas pendientes o en preparación en cocina.',
                    'errors' => ['orden_id' => ['No se puede cobrar la mesa porque aún tiene comandas pendientes o en preparación en cocina.']],
                ], 422);
            }

            // 4. Verificar si ya tiene un pago registrado
            $yaPagada = Pago::where('orden_id', $orden->id)
                ->where('estado', 'pagado')
                ->exists();

            if ($yaPagada) {
                return response()->json([
                    'mensaje' => 'Esta orden ya cuenta con un pago registrado.',
                    'message' => 'Esta orden ya cuenta con un pago registrado.',
                    'errors' => ['orden_id' => ['Esta orden ya fue pagada.']],
                ], 422);
            }

            // 5. Crear el pago
            $pago = Pago::create([
                'orden_id' => $orden->id,
                'metodo' => $metodoInput,
                'monto' => $orden->total,
                'estado' => 'pagado',
            ]);

            // 6. Cerrar la orden
            $orden->update([
                'estado' => 'cerrada',
            ]);

            // 7. CORRECCIÓN DEL BUG: Solo marcar la mesa como 'disponible' si NO le quedan más órdenes activas
            $ordenesActivasRestantes = Orden::where('mesa_id', $orden->mesa_id)
                ->where('id', '!=', $orden->id)
                ->whereIn('estado', ['pendiente', 'preparando', 'listo', 'entregado'])
                ->exists();

            if (! $ordenesActivasRestantes) {
                $orden->mesa()->update([
                    'estado' => 'disponible',
                ]);
            }

            return response()->json([
                'mensaje' => 'Pago registrado correctamente',
                'message' => 'Pago registrado correctamente',
                'pago' => $pago->load('orden.mesa'),
            ], 201);
        });
    }
}
