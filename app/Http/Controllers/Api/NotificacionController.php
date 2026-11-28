<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $notificaciones = $user->notifications()
            ->latest()
            ->take(30)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'type' => $n->type,
                'data' => $n->data,
                'read_at' => $n->read_at,
                'created_at' => $n->created_at,
            ]);

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'notificaciones' => $notificaciones,
        ]);
    }

    public function marcarLeida(Request $request, string $id)
    {
        $user = $request->user();
        $notificacion = $user->notifications()->where('id', $id)->first();

        if (! $notificacion) {
            return response()->json([
                'mensaje' => 'Notificación no encontrada',
            ], 404);
        }

        if (is_null($notificacion->read_at)) {
            $notificacion->markAsRead();
        }

        return response()->json([
            'mensaje' => 'Notificación marcada como leída',
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function marcarTodasLeidas(Request $request)
    {
        $user = $request->user();
        $user->unreadNotifications()->update(['read_at' => now()]);

        return response()->json([
            'mensaje' => 'Todas las notificaciones marcadas como leídas',
            'unread_count' => 0,
        ]);
    }
}
