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
     * Marcar una notificación individual de base de datos como leída con cortocircuito de envíos programados.
     */
    public function markAsRead(Request $request, string $id)
    {
        $user = Auth::user();
        $notification = $user->notifications()->where('id', $id)->firstOrFail();

        $result = \App\Services\NotificationCircuitBreakerService::markNotificationAsRead($notification, $user);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Notificación marcada como leída.',
                'cancelled_deliveries' => $result['cancelled_messages'],
            ]);
        }

        $redirectUrl = $notification->data['action_url'] ?? route('dashboard');
        return redirect($redirectUrl);
    }

    /**
     * Marcar todas las notificaciones de base de datos como leídas con cortocircuito de envíos programados.
     */
    public function markAllRead(Request $request)
    {
        $user = Auth::user();
        $result = \App\Services\NotificationCircuitBreakerService::markAllNotificationsAsRead($user);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Todas las notificaciones marcadas como leídas.',
                'cancelled_deliveries' => $result['cancelled_messages'],
            ]);
        }

        return back()->with('success', 'Todas las notificaciones marcadas como leídas y recordatorios programados cancelados.');
    }
}
