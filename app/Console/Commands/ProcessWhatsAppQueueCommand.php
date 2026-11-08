<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\WhatsAppNotificationService;
use Illuminate\Console\Command;

class ProcessWhatsAppQueueCommand extends Command
{
    protected $signature = 'whatsapp:process-pending
        {--retry-failed : Reencolar mensajes fallidos que aún tienen intentos disponibles}
        {--max-attempts=3 : Número máximo de intentos por mensaje}
        {--limit=100 : Máximo de mensajes fallidos a reencolar por ejecución}';

    protected $description = 'Supervisa la cola de WhatsApp y permite reencolar fallos para el motor Node.';

    public function handle(WhatsAppNotificationService $notifications): int
    {
        $maxAttempts = filter_var($this->option('max-attempts'), FILTER_VALIDATE_INT);
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT);

        if ($maxAttempts === false || $maxAttempts < 1 || $limit === false || $limit < 1) {
            $this->error('Las opciones --max-attempts y --limit deben ser enteros positivos.');

            return self::FAILURE;
        }

        if ($this->option('retry-failed')) {
            $retried = $notifications->retryFailed($maxAttempts, $limit);
            $this->info("Mensajes reencolados: {$retried}");
        } else {
            $this->comment('La entrega la realiza wa-motor; este comando supervisa la cola y no compite por los mensajes pendientes.');
        }

        $this->line('Pendientes: ' . $notifications->countByStatus('pendiente'));
        $this->line('Fallidos: ' . $notifications->countByStatus('fallido'));

        return self::SUCCESS;
    }
}
