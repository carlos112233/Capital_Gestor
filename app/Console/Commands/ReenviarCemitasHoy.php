<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pedido;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReenviarCemitasHoy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pedidos:reenviar-cemitas-hoy {--phone= : Número opcional de WhatsApp al que enviar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Busca los pedidos de cemitas/milanesas creados hoy y los reenvía por WhatsApp al admin';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Buscando pedidos de cemitas/milanesas del día de hoy...");

        $waService = app(\App\Services\WhatsAppNotificationService::class);
        $adminPhones = $waService->getAdminPhones($this->option('phone') ? (string) $this->option('phone') : null);

        // Obtener pedidos de hoy
        $pedidosHoy = Pedido::with(['articulo', 'user'])
            ->whereDate('created_at', Carbon::today())
            ->orderBy('id', 'asc')
            ->get();

        $cemitasPedididas = [];

        foreach ($pedidosHoy as $pedido) {
            if ($pedido->articulo && $pedido->articulo->esCemitaOMilanesa()) {
                $cemitasPedididas[] = $pedido;
            }
        }

        if (empty($cemitasPedididas)) {
            $this->warn("No se encontraron pedidos de cemitas o milanesas el día de hoy (" . Carbon::today()->format('Y-m-d') . ").");
            return 0;
        }

        $this->info("Se encontraron " . count($cemitasPedididas) . " pedidos de cemitas/milanesas de hoy. Encolando mensajes para: " . implode(', ', $adminPhones));
        $appName = \App\Models\AppSetting::getSettings()->app_name ?? 'El Bajón';

        // 1. Enviar mensaje individual por cada pedido
        foreach ($cemitasPedididas as $pedido) {
            $clienteNombre  = $pedido->user->name ?? 'Cliente';
            $articuloNombre = $pedido->articulo->nombre ?? 'N/A';
            $totalFormatted = number_format($pedido->costo ?? 0, 2);
            $hora           = $pedido->created_at->format('h:i A');

            $mensajeWa = "*📦 REENVIADO: PEDIDO #{$pedido->id} - {$appName}*\n\n" .
                         "• *Hora:* {$hora}\n" .
                         "• *Cliente:* {$clienteNombre}\n" .
                         "• *Artículo:* {$articuloNombre}\n" .
                         "• *Cantidad:* {$pedido->cantidad}\n" .
                         "• *Total:* \${$totalFormatted}\n" .
                         "• *Notas:* " . ($pedido->descripcion ?: 'Sin notas') . "\n\n" .
                         "_Recuperado y reenviado por {$appName}_";

            $waService->enqueueToMultiple($adminPhones, $mensajeWa);
        }

        // 2. Enviar también un mensaje RESUMEN CONSOLIDADO de todas las cemitas de hoy
        $resumenTexto = "*📊 RESUMEN TOTAL DE CEMITAS DE HOY (" . Carbon::today()->format('d/m/Y') . ")*\n\n";
        $totalGeneral = 0;
        foreach ($cemitasPedididas as $idx => $pedido) {
            $clienteNombre  = $pedido->user->name ?? 'Cliente';
            $articuloNombre = $pedido->articulo->nombre ?? 'N/A';
            $totalFormatted = number_format($pedido->costo ?? 0, 2);
            $hora           = $pedido->created_at->format('h:i A');
            $totalGeneral  += $pedido->costo;

            $resumenTexto .= "*Pedido #{$pedido->id}* ({$hora})\n" .
                             "• Cliente: {$clienteNombre}\n" .
                             "• Platillo: {$articuloNombre} (x{$pedido->cantidad})\n" .
                             "• Total: \${$totalFormatted}\n" .
                             "• Notas: " . ($pedido->descripcion ?: 'Sin notas') . "\n" .
                             "------------------------------------------\n";
        }
        $resumenTexto .= "\n*Total acumulado de cemitas hoy:* \$" . number_format($totalGeneral, 2) . "\n\n" .
                         "_Reporte automático generado por {$appName}_";

        $waService->enqueueToMultiple($adminPhones, $resumenTexto);

        $this->info("¡Mensajes encolados exitosamente a través de WhatsAppNotificationService!");
        return 0;
    }
}
