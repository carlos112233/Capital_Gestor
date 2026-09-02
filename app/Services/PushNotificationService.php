<?php

namespace App\Services;

use App\Models\User;
use App\Models\Pedido;
use App\Notifications\AppNotification;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    /**
     * Envía una notificación Push a todos los administradores del sistema.
     */
    public static function notifyAdmins(string $title, string $message, string $url = '/dashboardAdmin'): int
    {
        $admins = User::whereHas('roles', function ($q) {
            $q->where('name', 'admin');
        })->get();

        $count = 0;
        foreach ($admins as $admin) {
            try {
                $admin->notify(new AppNotification($title, $message, $url));
                $count++;
            } catch (\Throwable $e) {
                Log::error("Error enviando Push a admin {$admin->id}: " . $e->getMessage());
            }
        }

        return $count;
    }

    /**
     * Envía una notificación Push a un usuario específico.
     */
    public static function notifyUser(User $user, string $title, string $message, string $url = '/dashboard'): bool
    {
        try {
            $user->notify(new AppNotification($title, $message, $url));
            return true;
        } catch (\Throwable $e) {
            Log::error("Error enviando Push a usuario {$user->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envía las alertas programadas matutinas (8:30 AM, 9:15 AM, 9:55 AM).
     * Regla estricta: Busca pedidos en estado 'En preparación'.
     * Si no hay pedidos en preparación, NO envía ninguna notificación.
     * Si hay pedidos, envía 1 notificación Push individual por cada pedido al administrador.
     */
    public static function sendScheduledDeliveryReminders(): int
    {
        // Buscar pedidos en estado 'En preparación' (o 'en_preparacion')
        $pedidosEnPreparacion = Pedido::with('user')
            ->whereIn('status', ['En preparación', 'preparacion', 'Preparación', 'En preparacion', 'en_preparacion'])
            ->get();

        // Si no hay pedidos en preparación, no se envía nada
        if ($pedidosEnPreparacion->isEmpty()) {
            Log::info("Push Reminders Matutino: Sin pedidos en estado 'En preparación'. No se envía notificación.");
            return 0;
        }

        $sentCount = 0;

        // Enviar 1 notificación Push independiente por CADA pedido en cola
        foreach ($pedidosEnPreparacion as $pedido) {
            $clienteNombre = $pedido->user ? $pedido->user->name : 'Cliente';
            $numPedido = $pedido->id;
            $title = "Entrega Pendiente #{$numPedido} 🚚";
            $message = "El pedido #{$numPedido} del cliente {$clienteNombre} está listo para entrega. Haga clic para marcar como Entregado.";
            $url = route('admin.pedidos.index', ['highlight' => $numPedido]);

            self::notifyAdmins($title, $message, $url);
            $sentCount++;
        }

        Log::info("Push Reminders Matutino: Se enviaron {$sentCount} notificaciones individuales de entrega a administradores.");
        return $sentCount;
    }
}
