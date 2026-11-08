<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WhatsAppNotificationService
{
    public function enqueue(string $numero, string $mensaje, ?string $pdfPath = null): int
    {
        return $this->enqueuePayload([
            'numero' => $numero,
            'mensaje' => $mensaje,
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

        return (int) DB::table('whatsapp_pending_messages')->insertGetId([
            'numero' => $numero,
            'mensaje' => $mensaje,
            'pdf_path' => $payload['pdf_path'] ?? null,
            'status' => 'pendiente',
            'attempts' => 0,
            'error_message' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function markAsSent(int $messageId): bool
    {
        return DB::table('whatsapp_pending_messages')
            ->where('id', $messageId)
            ->where('status', 'pendiente')
            ->update([
                'status' => 'enviado',
                'error_message' => null,
                'updated_at' => now(),
            ]) > 0;
    }

    public function markAsFailed(int $messageId, string $error): bool
    {
        return DB::table('whatsapp_pending_messages')
            ->where('id', $messageId)
            ->where('status', 'pendiente')
            ->update([
                'status' => 'fallido',
                'error_message' => mb_substr($error, 0, 2000),
                'attempts' => DB::raw('COALESCE(attempts, 0) + 1'),
                'updated_at' => now(),
            ]) > 0;
    }

    public function retryFailed(int $maxAttempts = 3, int $limit = 100): int
    {
        if ($maxAttempts < 1 || $limit < 1) {
            throw new InvalidArgumentException('El máximo de intentos y el límite deben ser positivos.');
        }

        $ids = DB::table('whatsapp_pending_messages')
            ->where('status', 'fallido')
            ->where('attempts', '<', $maxAttempts)
            ->orderBy('updated_at')
            ->limit($limit)
            ->pluck('id');

        if ($ids->isEmpty()) {
            return 0;
        }

        return DB::table('whatsapp_pending_messages')
            ->whereIn('id', $ids)
            ->where('status', 'fallido')
            ->where('attempts', '<', $maxAttempts)
            ->update([
                'status' => 'pendiente',
                'error_message' => null,
                'updated_at' => now(),
            ]);
    }

    public function countByStatus(string $status): int
    {
        return DB::table('whatsapp_pending_messages')
            ->where('status', $status)
            ->count();
    }
}
