<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WhatsAppNotificationService
{
    public const TABLE = 'whatsapp_pending_messages';

    /**
     * Normaliza un número telefónico de México a formato WhatsApp (521XXXXXXXXXX).
     */
    public function formatPhone(?string $telefono): string
    {
        $num = preg_replace('/[^0-9]/', '', (string) $telefono);
        if (strlen($num) === 10) {
            return '521' . $num;
        }
        if (strlen($num) === 12 && str_starts_with($num, '52')) {
            return '521' . substr($num, 2);
        }

        return $num;
    }

    /**
     * Obtiene los teléfonos de los administradores registrados o el teléfono de respaldo de configuración.
     *
     * @return array<int, string>
     */
    public function getAdminPhones(?string $customPhone = null): array
    {
        if (!empty($customPhone)) {
            $formatted = $this->formatPhone($customPhone);
            if ($formatted !== '') {
                return [$formatted];
            }
        }

        $phones = DB::table('users')
            ->join('role_user', 'users.id', '=', 'role_user.user_id')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->whereIn(DB::raw('LOWER(roles.name)'), ['admin', 'administrador'])
            ->whereNotNull('users.telefono')
            ->where('users.telefono', '!=', '')
            ->pluck('users.telefono')
            ->map(fn ($tel) => $this->formatPhone((string) $tel))
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($phones)) {
            $fallback = $this->formatPhone((string) config('services.whatsapp.admin_fallback_phone', '5212222153410'));
            return $fallback !== '' ? [$fallback] : ['5212222153410'];
        }

        return $phones;
    }

    /**
     * Obtiene los teléfonos de los usuarios con rol cocina.
     *
     * @return array<int, string>
     */
    public function getCocinaPhones(): array
    {
        return DB::table('users')
            ->join('role_user', 'users.id', '=', 'role_user.user_id')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->whereIn(DB::raw('LOWER(roles.name)'), ['cocina'])
            ->whereNotNull('users.telefono')
            ->where('users.telefono', '!=', '')
            ->pluck('users.telefono')
            ->map(fn ($tel) => $this->formatPhone((string) $tel))
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }

    public function enqueue(string $numero, string $mensaje, ?string $pdfPath = null): int
    {
        return $this->enqueuePayload([
            'numero'   => $numero,
            'mensaje'  => $mensaje,
            'pdf_path' => $pdfPath,
        ]);
    }

    public function enqueuePayload(array $payload): int
    {
        $numero = trim((string) ($payload['numero'] ?? ''));
        $mensaje = (string) ($payload['mensaje'] ?? '');
        if ($numero === '' || trim($mensaje) === '') {
            throw new InvalidArgumentException('El número y el mensaje de WhatsApp son obligatorios.');
        }

        return (int) DB::table(self::TABLE)->insertGetId([
            'numero'        => $numero,
            'mensaje'       => $mensaje,
            'pdf_path'      => $payload['pdf_path'] ?? null,
            'status'        => 'pendiente',
            'attempts'      => 0,
            'error_message' => null,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    /**
     * Encola un mensaje para múltiples destinatarios.
     *
     * @param array<int, string> $numeros
     */
    public function enqueueToMultiple(array $numeros, string $mensaje, ?string $pdfPath = null): int
    {
        $count = 0;
        foreach (array_unique(array_filter($numeros)) as $numero) {
            $this->enqueue((string) $numero, $mensaje, $pdfPath);
            $count++;
        }

        return $count;
    }

    /**
     * Encola un mensaje para todos los administradores (o lista provista).
     *
     * @param array<int, string>|null $adminPhones
     */
    public function enqueueToAdmins(string $mensaje, ?string $pdfPath = null, ?array $adminPhones = null): int
    {
        $targets = $adminPhones ?? $this->getAdminPhones();
        return $this->enqueueToMultiple($targets, $mensaje, $pdfPath);
    }

    /**
     * Obtiene los mensajes más recientes de la cola de WhatsApp.
     */
    public function getRecentMessages(int $limit = 20): Collection
    {
        return DB::table(self::TABLE)
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }

    public function markAsSent(int $messageId, bool $onlyIfPending = true): bool
    {
        $query = DB::table(self::TABLE)->where('id', $messageId);
        if ($onlyIfPending) {
            $query->where('status', 'pendiente');
        }

        return $query->update([
            'status'        => 'enviado',
            'error_message' => null,
            'updated_at'    => now(),
        ]) > 0;
    }

    public function markAllPendingAsSent(): int
    {
        return DB::table(self::TABLE)
            ->where('status', 'pendiente')
            ->update([
                'status'        => 'enviado',
                'error_message' => null,
                'updated_at'    => now(),
            ]);
    }

    public function markAsPending(int $messageId): bool
    {
        return DB::table(self::TABLE)
            ->where('id', $messageId)
            ->update([
                'status'        => 'pendiente',
                'error_message' => null,
                'updated_at'    => now(),
            ]) > 0;
    }

    public function markAsFailed(int $messageId, string $error): bool
    {
        return DB::table(self::TABLE)
            ->where('id', $messageId)
            ->where('status', 'pendiente')
            ->update([
                'status'        => 'fallido',
                'error_message' => mb_substr($error, 0, 2000),
                'attempts'      => DB::raw('COALESCE(attempts, 0) + 1'),
                'updated_at'    => now(),
            ]) > 0;
    }

    /**
     * Cancela mensajes pendientes (corta el circuito cuando el admin ya leyó la notificación en web).
     *
     * @param array<int, string> $keywords
     */
    public function cancelPendingMessages(array $keywords = [], ?string $reason = null): int
    {
        $query = DB::table(self::TABLE)->where('status', 'pendiente');

        if (!empty($keywords)) {
            $query->where(function ($q) use ($keywords) {
                foreach ($keywords as $kw) {
                    if ($kw !== '') {
                        $q->orWhere('mensaje', 'like', "%{$kw}%");
                    }
                }
            });
        }

        $updatePayload = [
            'status'     => 'cancelado_por_lectura',
            'updated_at' => now(),
        ];

        if ($reason !== null) {
            $updatePayload['error_message'] = $reason;
        }

        return $query->update($updatePayload);
    }

    /**
     * Cancela mensajes pendientes para un conjunto de variantes telefónicas de un usuario y concepto opcional.
     *
     * @param array<int, string> $phoneVariants
     */
    public function cancelPendingForUserPhones(array $phoneVariants, string $last10, ?string $concept = null): int
    {
        $query = DB::table(self::TABLE)
            ->where('status', 'pendiente')
            ->where(function ($q) use ($phoneVariants, $last10) {
                $q->whereIn('numero', $phoneVariants);
                if ($last10 !== '') {
                    $q->orWhere('numero', 'like', "%{$last10}%");
                }
            });

        if ($concept !== null && $concept !== '') {
            $conceptLower = mb_strtolower($concept, 'UTF-8');
            if (in_array($conceptLower, ['saldo', 'recordatorio', 'deuda'], true)) {
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

        return $query->update([
            'status'     => 'cancelado',
            'updated_at' => now(),
        ]);
    }

    public function retryFailed(int $maxAttempts = 3, int $limit = 100): int
    {
        if ($maxAttempts < 1 || $limit < 1) {
            throw new InvalidArgumentException('El máximo de intentos y el límite deben ser positivos.');
        }

        $ids = DB::table(self::TABLE)
            ->where('status', 'fallido')
            ->where('attempts', '<', $maxAttempts)
            ->orderBy('updated_at')
            ->limit($limit)
            ->pluck('id');

        if ($ids->isEmpty()) {
            return 0;
        }

        return DB::table(self::TABLE)
            ->whereIn('id', $ids)
            ->where('status', 'fallido')
            ->where('attempts', '<', $maxAttempts)
            ->update([
                'status'        => 'pendiente',
                'error_message' => null,
                'updated_at'    => now(),
            ]);
    }

    public function countByStatus(string $status): int
    {
        return DB::table(self::TABLE)
            ->where('status', $status)
            ->count();
    }
}
