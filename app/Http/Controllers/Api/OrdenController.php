<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mesa;
use App\Models\Orden;
use App\Models\OrdenDetalle;
use App\Models\Platillo;
use App\Notifications\MesaListaParaEntregarNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrdenController extends Controller
{
    public function index()
    {
        $ordenes = Orden::with([
            'mesa',
            'user',
            'detalles.platillo',
        ])
            ->latest()
            ->get();

        return response()->json($ordenes);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'mesa_id' => 'required|exists:mesas,id',

            'items' => 'required|array|min:1',

            'items.*.platillo_id' => 'required|exists:platillos,id',
            'items.*.cantidad' => 'required|integer|min:1',
            'items.*.notas' => 'nullable|string|max:500',
        ]);

        $orden = DB::transaction(function () use ($request, $datos) {

            $orden = Orden::create([
                'mesa_id' => $datos['mesa_id'],

                // El usuario sale directamente del token Sanctum
                'user_id' => $request->user()->id,

                'estado' => 'pendiente',

                'total' => 0,
            ]);

            $total = 0;

            foreach ($datos['items'] as $item) {

                $platillo = Platillo::where('disponible', true)
                    ->findOrFail($item['platillo_id']);

                $precio = $platillo->precio;

                $subtotal = $precio * $item['cantidad'];

                $total += $subtotal;

                OrdenDetalle::create([
                    'orden_id' => $orden->id,
                    'platillo_id' => $platillo->id,
                    'cantidad' => $item['cantidad'],
                    'precio' => $precio,
                    'notas' => $item['notas'] ?? null,
                    'estado' => 'pendiente',
                ]);
            }

            $orden->update([
                'total' => $total,
            ]);

            Mesa::where('id', $datos['mesa_id'])
                ->update([
                    'estado' => 'ocupada',
                ]);

            return $orden;
        });

        return response()->json([
            'mensaje' => 'Orden registrada correctamente',

            'orden' => $orden->load([
                'mesa',
                'user',
                'detalles.platillo',
            ]),
        ], 201);
    }

    public function show(Orden $orden)
    {
        return response()->json(
            $orden->load([
                'mesa',
                'user',
                'detalles.platillo',
            ])
        );
    }

    public function updateEstado(Request $request, Orden $orden)
    {
        $datos = $request->validate([
            'estado' => 'required|in:pendiente,preparando,listo,entregado,cancelada',
        ]);

        if ($datos['estado'] === 'entregado') {
            $tieneNoListos = OrdenDetalle::whereHas('orden', function ($q) use ($orden) {
                $q->where('mesa_id', $orden->mesa_id)
                    ->whereIn('estado', ['pendiente', 'preparando', 'listo']);
            })
                ->whereIn('estado', ['pendiente', 'preparando'])
                ->exists();

            if ($tieneNoListos) {
                return response()->json([
                    'mensaje' => 'No se puede entregar la mesa mientras haya platillos pendientes o en preparación.',
                ], 422);
            }
        }

        DB::transaction(function () use ($orden, $datos) {
            $orden->update([
                'estado' => $datos['estado'],
            ]);

            if (in_array($datos['estado'], ['pendiente', 'preparando', 'listo', 'entregado'], true)) {
                $orden->detalles()
                    ->where('estado', '!=', 'entregado')
                    ->update(['estado' => $datos['estado']]);
            }
        });

        return response()->json([
            'mensaje' => 'Estado actualizado correctamente',
            'orden' => $orden->fresh(['mesa', 'user', 'detalles.platillo']),
        ]);
    }

    public function updateDetalleEstado(Request $request, OrdenDetalle $ordenDetalle)
    {
        $datos = $request->validate([
            'estado' => 'required|in:pendiente,preparando,listo',
        ]);

        $orden = $ordenDetalle->orden;

        if (! $orden || in_array($orden->estado, ['entregado', 'cerrada', 'cancelada'], true) || $ordenDetalle->estado === 'entregado') {
            return response()->json([
                'mensaje' => 'No se puede modificar un platillo de una orden ya entregada, cerrada o cancelada.',
            ], 422);
        }

        DB::transaction(function () use ($ordenDetalle, $orden, $datos) {
            $ordenDetalle->update([
                'estado' => $datos['estado'],
            ]);

            $estadosDetalles = $orden->detalles()->pluck('estado');
            $nuevoEstadoOrden = $this->calcularEstadoOrdenDesdeDetalles($estadosDetalles);

            if ($orden->estado !== $nuevoEstadoOrden) {
                $orden->update(['estado' => $nuevoEstadoOrden]);
            }
        });

        return response()->json([
            'mensaje' => 'Estado del platillo actualizado correctamente',
            'detalle' => $ordenDetalle->fresh('platillo'),
            'orden' => $orden->fresh(['mesa', 'user', 'detalles.platillo']),
        ]);
    }

    public function entregarMesa(Request $request, Mesa $mesa)
    {
        $resultado = DB::transaction(function () use ($request, $mesa) {
            $ordenesActivas = Orden::where('mesa_id', $mesa->id)
                ->whereIn('estado', ['pendiente', 'preparando', 'listo'])
                ->with(['detalles', 'user'])
                ->lockForUpdate()
                ->get();

            if ($ordenesActivas->isEmpty()) {
                return [
                    'status' => 200,
                    'body' => [
                        'mensaje' => 'La mesa ya fue marcada como entregada o no tiene platillos activos.',
                        'mesa' => $mesa,
                        'ordenes_entregadas' => [],
                        'notificacion_enviada' => false,
                        'notificados' => 0,
                    ],
                ];
            }

            $noListos = $ordenesActivas
                ->flatMap(fn ($orden) => $orden->detalles)
                ->filter(fn ($detalle) => $detalle->estado !== 'listo')
                ->count();

            if ($noListos > 0) {
                return [
                    'status' => 422,
                    'body' => [
                        'mensaje' => 'No se puede entregar la mesa mientras haya platillos pendientes o en preparación.',
                        'pendientes_o_preparando' => $noListos,
                    ],
                ];
            }

            $ordenIds = $ordenesActivas->pluck('id')->sort()->values()->all();

            OrdenDetalle::whereIn('orden_id', $ordenIds)
                ->where('estado', '!=', 'entregado')
                ->update(['estado' => 'entregado']);

            Orden::whereIn('id', $ordenIds)
                ->update(['estado' => 'entregado']);

            $entregaKey = 'mesa_'.$mesa->id.'_ordenes_'.implode('_', $ordenIds);
            $entregadoPor = $request->user()?->name;

            $meseros = $ordenesActivas
                ->pluck('user')
                ->filter()
                ->unique('id');

            $notificados = 0;

            foreach ($meseros as $mesero) {
                $yaNotificado = $mesero->unreadNotifications()
                    ->where('type', MesaListaParaEntregarNotification::class)
                    ->get()
                    ->contains(fn ($n) => ($n->data['entrega_key'] ?? null) === $entregaKey);

                if (! $yaNotificado) {
                    $mesero->notify(new MesaListaParaEntregarNotification(
                        $mesa,
                        $ordenIds,
                        $entregaKey,
                        $entregadoPor,
                    ));
                    $notificados++;
                }
            }

            return [
                'status' => 200,
                'body' => [
                    'mensaje' => "Mesa {$mesa->numero} entregada correctamente. Se notificó al mesero responsable.",
                    'mesa' => $mesa->fresh(),
                    'ordenes_entregadas' => $ordenIds,
                    'notificacion_enviada' => $notificados > 0,
                    'notificados' => $notificados,
                ],
            ];
        });

        return response()->json($resultado['body'], $resultado['status']);
    }

    private function calcularEstadoOrdenDesdeDetalles($estadosDetalles): string
    {
        if ($estadosDetalles->isEmpty()) {
            return 'pendiente';
        }

        if ($estadosDetalles->every(fn ($e) => $e === 'entregado')) {
            return 'entregado';
        }

        if ($estadosDetalles->every(fn ($e) => in_array($e, ['listo', 'entregado'], true))
            && $estadosDetalles->contains('listo')) {
            return 'listo';
        }

        if ($estadosDetalles->contains(fn ($e) => in_array($e, ['preparando', 'listo'], true))) {
            return 'preparando';
        }

        return 'pendiente';
    }
}
