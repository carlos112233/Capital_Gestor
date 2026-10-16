<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class NotificationCircuitBreakerService
{
    /**
     * Cortocircuita y cancela entregas programadas (WhatsApp pendientes y jobs en cola) para un usuario.
     *
     * @param User $user El usuario cuyas entregas programadas se cancelan.
     * @param string|null $concept Concepto o palabra clave a filtrar (ej. 'saldo', 'pedido', etc.) o null para todas.
     * @return int Cantidad de mensajes de WhatsApp cancelados.
     */
    public static function cancelPendingDeliveries(User $user, ?string $concept = null): int
    {
        $cancelledCount = 0;

        if (!empty($user->telefono)) {
            $raw = preg_replace('/[^0-9]/', '', (string) $user->telefono);
            if (!empty($raw)) {
                $last10 = strlen($raw) >= 10 ? substr($raw, -10) : $raw;

                $phoneVariants = array_unique(array_filter([
                    $raw,
                    $last10,
                    '52' . $last10,
                    '521' . $last10,
                ]));

                $query = DB::table('whatsapp_pending_messages')
                    ->where('status', 'pendiente')
                    ->where(function ($q) use ($phoneVariants, $last10) {
                        $q->whereIn('numero', $phoneVariants);
                        if (!empty($last10)) {
                            $q->orWhere('numero', 'like', "%{$last10}%");
                        }
                    });

                // Si se especificó un concepto específico, filtrar los mensajes relevantes
                if ($concept !== null && $concept !== '') {
                    $conceptLower = mb_strtolower($concept, 'UTF-8');
                    if ($conceptLower === 'saldo' || $conceptLower === 'recordatorio' || $conceptLower === 'deuda') {
                        $query->where(function ($q) {
                            $q->whereRaw('LOWER(mensaje) LIKE ?', ['%saldo%'])
                              ->orWhereRaw('LOWER(mensaje) LIKE ?', ['%estado de cuenta%'])
                              ->orWhereRaw('LOWER(mensaje) LIKE ?', ['%recordatorio%'])
                              ->orWhereRaw('LOWER(mensaje) LIKE ?', ['%cobro%'])
                              ->orWhereRaw('LOWER(mensaje) LIKE ?', ['%deuda%']);
                        });
                    } else {
                        $query->where(function ($q) use ($conceptLower) {
                            $q->whereRaw('LOWER(mensaje) LIKE ?', ["%{$conceptLower}%"]);
                        });
                    }
                }

                $cancelledCount = $query->update([
                    'status' => 'cancelado',
                    'updated_at' => now(),
                ]);

                if ($cancelledCount > 0) {
                    Log::info("Cortocircuito de notificaciones: Se cancelaron {$cancelledCount} mensaje(s) de WhatsApp pendientes para el usuario #{$user->id} ({$user->name}). Concepto: " . ($concept ?? 'General/Todos'));
                }
            }
        }

        // Cancelar jobs asociados en la tabla 'jobs' si existen
        self::cancelQueuedJobsForUser($user, $concept);

        return $cancelledCount;
    }

    /**
     * Cancela jobs programados en la tabla 'jobs' de Laravel Queue para el usuario.
     */
    public static function cancelQueuedJobsForUser(User $user, ?string $concept = null): int
    {
        try {
            if (!Schema::hasTable('jobs')) {
                return 0;
            }

            $jobs = DB::table('jobs')->get();
            $cancelledJobs = 0;

            foreach ($jobs as $job) {
                $payload = (string) $job->payload;

                // Verificar si el job está asociado al usuario
                $matchesUser = str_contains($payload, "\"user_id\":{$user->id}")
                    || str_contains($payload, "User\";i:{$user->id}")
                    || str_contains($payload, "\"id\":{$user->id}");

                if ($matchesUser) {
                    if ($concept === null || str_contains(mb_strtolower($payload, 'UTF-8'), mb_strtolower($concept, 'UTF-8'))) {
                        DB::table('jobs')->where('id', $job->id)->delete();
                        $cancelledJobs++;
                    }
                }
            }

            if ($cancelledJobs > 0) {
                Log::info("Cortocircuito de jobs: Se eliminaron {$cancelledJobs} job(s) en cola para el usuario #{$user->id}.");
            }

            return $cancelledJobs;
        } catch (\Throwable $e) {
            Log::warning("Error al inspeccionar jobs en cola para cortocircuito: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Cortocircuito al marcar una notificación específica como leída.
     */
    public static function markNotificationAsRead(DatabaseNotification $notification, User $user): array
    {
        $notification->markAsRead();

        // Extraer concepto del payload si existe
        $concept = null;
        $data = $notification->data ?? [];

        if (!empty($data['title'])) {
            $title = mb_strtolower((string) $data['title'], 'UTF-8');
            if (str_contains($title, 'recordatorio') || str_contains($title, 'saldo') || str_contains($title, 'pago') || str_contains($title, 'comprobante')) {
                $concept = 'saldo';
            } elseif (preg_match('/pedido\s*#?(\d+)/i', $title, $m)) {
                $concept = $m[1];
            }
        }

        $cancelledCount = self::cancelPendingDeliveries($user, $concept);

        return [
            'notification_id' => $notification->id,
            'cancelled_messages' => $cancelledCount,
        ];
    }

    /**
     * Cortocircuito al marcar todas las notificaciones de un usuario como leídas.
     */
    public static function markAllNotificationsAsRead(User $user): array
    {
        $unreadCount = $user->unreadNotifications()->update(['read_at' => now()]);
        $cancelledCount = self::cancelPendingDeliveries($user, null);

        return [
            'read_notifications' => $unreadCount,
            'cancelled_messages' => $cancelledCount,
        ];
    }
}

