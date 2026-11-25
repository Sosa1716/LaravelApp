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
            'orden.user',
        ])
            ->latest()
            ->get();

        return response()->json($pagos);
    }

    public function store(Request $request)
    {
        // 1. Normalizar método de pago
        $metodoRaw = strtolower(trim((string) $request->input('metodo', 'efectivo')));
        $metodo = in_array($metodoRaw, ['tarjeta', 'card', 'credit', 'debit']) ? 'tarjeta' : 'efectivo';

        // 2. Extraer identificadores
        $ordenId = $request->input('orden_id');
        if (is_array($ordenId)) {
            $ordenId = reset($ordenId);
        }
        $mesaId = $request->input('mesa_id');

        // Cobro por mesa completa
        if (empty($ordenId) && ! empty($mesaId)) {
            return $this->cobrarPorMesa($mesaId, $metodo);
        }

        if (empty($ordenId) && empty($mesaId)) {
            return response()->json([
                'mensaje' => 'Debes indicar el número de comanda (orden_id) o mesa (mesa_id).',
                'message' => 'Debes indicar el número de comanda (orden_id) o mesa (mesa_id).',
                'errors' => ['orden_id' => ['El campo orden_id o mesa_id es obligatorio.']],
            ], 422);
        }

        // Cobro por comanda específica
        return $this->cobrarPorOrden($ordenId, $metodo);
    }

    private function cobrarPorOrden($ordenId, string $metodo)
    {
        return DB::transaction(function () use ($ordenId, $metodo) {
            $orden = Orden::lockForUpdate()->find($ordenId);

            if (! $orden) {
                return response()->json([
                    'mensaje' => 'La comanda solicitada no existe.',
                    'message' => 'La comanda solicitada no existe.',
                    'errors' => ['orden_id' => ['La comanda solicitada no existe.']],
                ], 422);
            }

            // IDEMPOTENCIA: Si la comanda ya tiene un pago registrado o ya está cerrada,
            // devolvemos 200 con el pago para que la app móvil limpie la pantalla sin errores.
            $pagoExistente = Pago::where('orden_id', $orden->id)
                ->where('estado', 'pagado')
                ->latest()
                ->first();

            if ($pagoExistente || $orden->estado === 'cerrada') {
                $quedanSinPagar = Orden::where('mesa_id', $orden->mesa_id)
                    ->where('id', '!=', $orden->id)
                    ->whereNotIn('estado', ['cerrada', 'cancelada'])
                    ->exists();

                if (! $quedanSinPagar && $orden->mesa) {
                    $orden->mesa()->update(['estado' => 'disponible']);
                }

                return response()->json([
                    'mensaje' => 'Esta orden ya fue cobrada y cerrada exitosamente.',
                    'message' => 'Esta orden ya fue cobrada y cerrada exitosamente.',
                    'pago' => $pagoExistente ? $pagoExistente->load('orden.mesa') : null,
                ], 200);
            }

            // Registrar el pago
            $pago = Pago::create([
                'orden_id' => $orden->id,
                'metodo' => $metodo,
                'monto' => $orden->total,
                'estado' => 'pagado',
            ]);

            // Marcar comanda como cerrada (liquidada)
            $orden->update([
                'estado' => 'cerrada',
            ]);

            // GESTIÓN DE ESTADO DE LA MESA:
            // Si la mesa aún tiene otras comandas activas pendientes de pago, permanece ocupada.
            // Si ya no quedan más comandas pendientes, la mesa queda libre (disponible).
            $quedanComandasSinPagar = Orden::where('mesa_id', $orden->mesa_id)
                ->where('id', '!=', $orden->id)
                ->whereNotIn('estado', ['cerrada', 'cancelada'])
                ->exists();

            if ($quedanComandasSinPagar) {
                $orden->mesa()->update(['estado' => 'ocupada']);
            } else {
                $orden->mesa()->update(['estado' => 'disponible']);
            }

            return response()->json([
                'mensaje' => 'Pago registrado correctamente',
                'message' => 'Pago registrado correctamente',
                'pago' => $pago->load('orden.mesa'),
            ], 201);
        });
    }

    private function cobrarPorMesa($mesaId, string $metodo)
    {
        $mesa = Mesa::find($mesaId);

        if (! $mesa) {
            return response()->json([
                'mensaje' => 'La mesa indicada no existe.',
                'message' => 'La mesa indicada no existe.',
                'errors' => ['mesa_id' => ['La mesa indicada no existe.']],
            ], 422);
        }

        // Buscar todas las órdenes de la mesa que no hayan sido cerradas ni canceladas
        $ordenesPorCobrar = Orden::where('mesa_id', $mesa->id)
            ->whereNotIn('estado', ['cerrada', 'cancelada'])
            ->get();

        if ($ordenesPorCobrar->isEmpty()) {
            $mesa->update(['estado' => 'disponible']);

            return response()->json([
                'mensaje' => 'La mesa no tiene cobros pendientes y se encuentra disponible.',
                'message' => 'La mesa no tiene cobros pendientes y se encuentra disponible.',
                'pago' => null,
            ], 200);
        }

        return DB::transaction(function () use ($ordenesPorCobrar, $mesa, $metodo) {
            $pagos = [];
            foreach ($ordenesPorCobrar as $o) {
                $p = Pago::create([
                    'orden_id' => $o->id,
                    'metodo' => $metodo,
                    'monto' => $o->total,
                    'estado' => 'pagado',
                ]);
                $o->update(['estado' => 'cerrada']);
                $pagos[] = $p;
            }

            // Como todas las comandas fueron liquidadas, la mesa queda libre
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
}
