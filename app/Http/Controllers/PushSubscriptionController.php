<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PushSubscriptionController extends Controller
{
    /**
     * Guarda o actualiza la suscripción Push del usuario autenticado.
     */
    public function store(Request $request)
    {
        $request->validate([
            'endpoint'    => 'required|string',
            'keys.auth'   => 'required|string',
            'keys.p256dh' => 'required|string',
        ]);

        $user = Auth::user();

        // El paquete updatePushSubscription usa los keys y endpoint que envía el objeto genérico de la API Web Push
        $user->updatePushSubscription(
            $request->endpoint,
            $request->keys['p256dh'],
            $request->keys['auth']
        );

        return response()->json(['success' => true, 'message' => 'Suscripción guardada correctamente.']);
    }

    /**
     * Elimina la suscripción Push del usuario (por ejemplo, al cerrar sesión).
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'endpoint' => 'required|string',
        ]);

        $user = Auth::user();
        $user->deletePushSubscription($request->endpoint);

        return response()->json(['success' => true, 'message' => 'Suscripción eliminada.']);
    }

    /**
     * Marcar todas las notificaciones de base de datos como leídas.
     */
    public function markAllRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        return back()->with('success', 'Todas las notificaciones marcadas como leídas.');
    }
}
