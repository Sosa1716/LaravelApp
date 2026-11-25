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
        // 1. Normalizar método de pago (insensible a mayúsculas, espacios y variantes)
        $metodoRaw = strtolower(trim((string) $request->input('metodo', 'efectivo')));
        $metodo = in_array($metodoRaw, ['tarjeta', 'card', 'credit', 'debit']) ? 'tarjeta' : 'efectivo';

        // 2. Extraer identificadores de orden o mesa de forma flexible
        $ordenId = $request->input('orden_id');
        if (is_array($ordenId)) {
            $ordenId = reset($ordenId);
        }
        $mesaId = $request->input('mesa_id');

        // Caso A: Si se envió únicamente mesa_id (o cobro de cuenta total de la mesa)
        if (empty($ordenId) && ! empty($mesaId)) {
            return $this->cobrarPorMesa($mesaId, $metodo);
        }

        // Si no se proporcionó ni orden_id ni mesa_id
        if (empty($ordenId) && empty($mesaId)) {
            return response()->json([
                'mensaje' => 'Debes indicar el número de orden (orden_id) o mesa (mesa_id).',
                'message' => 'Debes indicar el número de orden (orden_id) o mesa (mesa_id).',
                'errors' => ['orden_id' => ['El campo orden_id o mesa_id es obligatorio.']],
            ], 422);
        }

        // Caso B: Cobro por orden_id específico
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

            // IDEMPOTENCIA Y PROTECCIÓN DE MESAS CERRADAS:
            // Si la orden ya está cerrada o ya tiene un pago registrado:
            $pagoExistente = Pago::where('orden_id', $orden->id)
                ->where('estado', 'pagado')
                ->latest()
                ->first();

            if ($pagoExistente || $orden->estado === 'cerrada') {
                // Asegurar que si la mesa no tiene otras órdenes activas, quede disponible
                $quedanActivas = Orden::where('mesa_id', $orden->mesa_id)
                    ->where('id', '!=', $orden->id)
                    ->whereIn('estado', ['pendiente', 'preparando', 'listo', 'entregado'])
                    ->exists();

                if (! $quedanActivas && $orden->mesa) {
                    $orden->mesa()->update(['estado' => 'disponible']);
                }

                // Devolver HTTP 200 en lugar de 422 para evitar que la app móvil se trabe en bucle de error
                return response()->json([
                    'mensaje' => 'Esta orden ya fue cobrada y cerrada exitosamente.',
                    'message' => 'Esta orden ya fue cobrada y cerrada exitosamente.',
                    'pago' => $pagoExistente ? $pagoExistente->load('orden.mesa') : null,
                ], 200);
            }

            // Permitir cobro si la comanda está en 'entregado' o 'listo'
            if (! in_array($orden->estado, ['entregado', 'listo'])) {
                return response()->json([
                    'mensaje' => "La comanda #{$orden->id} está en preparación ({$orden->estado}). Debe estar lista o entregada para cobrar.",
                    'message' => "La comanda #{$orden->id} está en preparación ({$orden->estado}). Debe estar lista o entregada para cobrar.",
                    'errors' => ['orden_id' => ['La comanda debe estar lista o entregada antes de realizar el pago.']],
                ], 422);
            }

            // Registrar el pago
            $pago = Pago::create([
                'orden_id' => $orden->id,
                'metodo' => $metodo,
                'monto' => $orden->total,
                'estado' => 'pagado',
            ]);

            // Marcar comanda como cerrada
            $orden->update([
                'estado' => 'cerrada',
            ]);

            // RESOLUCIÓN DEL BUG DE COBROS PENDIENTES EN MESAS:
            // Comprobar si en la mesa aún quedan otras órdenes activas (entregadas, listas o en cocina)
            $quedanOrdenesActivas = Orden::where('mesa_id', $orden->mesa_id)
                ->where('id', '!=', $orden->id)
                ->whereIn('estado', ['pendiente', 'preparando', 'listo', 'entregado'])
                ->exists();

            if ($quedanOrdenesActivas) {
                // Aún hay comandas pendientes de esa mesa: la mesa PERMANECE OCUPADA
                $orden->mesa()->update(['estado' => 'ocupada']);
            } else {
                // No quedan más comandas activas en la mesa: se libera a DISPONIBLE
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

        // Buscar todas las órdenes de esa mesa que estén listas o entregadas (pendientes de cobro)
        $ordenesPorCobrar = Orden::where('mesa_id', $mesa->id)
            ->whereIn('estado', ['entregado', 'listo'])
            ->get();

        if ($ordenesPorCobrar->isEmpty()) {
            // Verificar si hay órdenes aún en cocina
            $enCocina = Orden::where('mesa_id', $mesa->id)
                ->whereIn('estado', ['pendiente', 'preparando'])
                ->exists();

            if ($enCocina) {
                return response()->json([
                    'mensaje' => 'La mesa aún tiene órdenes en preparación en cocina. Deben estar listas antes de cobrar.',
                    'message' => 'La mesa aún tiene órdenes en preparación en cocina. Deben estar listas antes de cobrar.',
                    'errors' => ['mesa_id' => ['La mesa aún tiene órdenes en preparación en cocina.']],
                ], 422);
            }

            // Si no tiene órdenes activas, asegurarse de que la mesa esté disponible
            $mesa->update(['estado' => 'disponible']);

            return response()->json([
                'mensaje' => 'La mesa no tiene cobros pendientes y se encuentra cerrada.',
                'message' => 'La mesa no tiene cobros pendientes y se encuentra cerrada.',
                'pago' => null,
            ], 200);
        }

        return DB::transaction(function () use ($ordenesPorCobrar, $mesa, $metodo) {
            $pagos = [];
            foreach ($ordenesPorCobrar as $o) {
                $yaPagada = Pago::where('orden_id', $o->id)->where('estado', 'pagado')->exists();
                if (! $yaPagada) {
                    $p = Pago::create([
                        'orden_id' => $o->id,
                        'metodo' => $metodo,
                        'monto' => $o->total,
                        'estado' => 'pagado',
                    ]);
                    $pagos[] = $p;
                }
                $o->update(['estado' => 'cerrada']);
            }

            // Comprobar si aún quedan órdenes pendientes en cocina
            $quedanEnCocina = Orden::where('mesa_id', $mesa->id)
                ->whereIn('estado', ['pendiente', 'preparando'])
                ->exists();

            if (! $quedanEnCocina) {
                $mesa->update(['estado' => 'disponible']);
            } else {
                $mesa->update(['estado' => 'ocupada']);
            }

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
