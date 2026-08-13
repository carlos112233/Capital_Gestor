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

        $formatPhone = function ($tel) {
            $num = preg_replace('/[^0-9]/', '', $tel);
            if (strlen($num) == 10) return '521' . $num;
            if (strlen($num) == 12 && str_starts_with($num, '52')) return '521' . substr($num, 2);
            return $num;
        };

        // Teléfonos de destino
        $customPhone = $this->option('phone');
        if ($customPhone) {
            $adminPhones = [$formatPhone($customPhone)];
        } else {
            $adminPhones = DB::table('users')
                ->join('role_user', 'users.id', '=', 'role_user.user_id')
                ->join('roles', 'role_user.role_id', '=', 'roles.id')
                ->whereIn(DB::raw('LOWER(roles.name)'), ['admin', 'administrador'])
                ->whereNotNull('users.telefono')
                ->where('users.telefono', '!=', '')
                ->pluck('users.telefono')
                ->map($formatPhone)
                ->filter()->unique()->toArray();

            if (empty($adminPhones)) {
                $adminPhones = ['5212222153410'];
            }
        }

        // Obtener pedidos de hoy
        $pedidosHoy = Pedido::with(['articulo', 'user'])
            ->whereDate('created_at', Carbon::today())
            ->orderBy('id', 'asc')
            ->get();

        $cemitasPedididas = [];

        foreach ($pedidosHoy as $pedido) {
            $articuloNombre = $pedido->articulo->nombre ?? 'N/A';
            $descripcionStr = mb_strtolower($articuloNombre . ' ' . ($pedido->descripcion ?? ''));

            if (str_contains($descripcionStr, 'cemita') || str_contains($descripcionStr, 'milanesa')) {
                $cemitasPedididas[] = $pedido;
            }
        }

        if (empty($cemitasPedididas)) {
            $this->warn("No se encontraron pedidos de cemitas o milanesas el día de hoy (" . Carbon::today()->format('Y-m-d') . ").");
            return 0;
        }

        $this->info("Se encontraron " . count($cemitasPedididas) . " pedidos de cemitas/milanesas de hoy. Encolando mensajes para: " . implode(', ', $adminPhones));

        // 1. Enviar mensaje individual por cada pedido
        foreach ($cemitasPedididas as $pedido) {
            $clienteNombre  = $pedido->user->name ?? 'Cliente';
            $articuloNombre = $pedido->articulo->nombre ?? 'N/A';
            $totalFormatted = number_format($pedido->costo ?? 0, 2);
            $hora           = $pedido->created_at->format('h:i A');

            $mensajeWa = "*📦 REENVIADO: PEDIDO #{$pedido->id} - El bajon*\n\n" .
                         "• *Hora:* {$hora}\n" .
                         "• *Cliente:* {$clienteNombre}\n" .
                         "• *Artículo:* {$articuloNombre}\n" .
                         "• *Cantidad:* {$pedido->cantidad}\n" .
                         "• *Total:* \${$totalFormatted}\n" .
                         "• *Notas:* " . ($pedido->descripcion ?: 'Sin notas') . "\n\n" .
                         "_Recuperado y reenviado por El bajon_";

            foreach ($adminPhones as $telAdmin) {
                DB::table('whatsapp_pending_messages')->insert([
                    'numero'     => $telAdmin,
                    'mensaje'    => $mensajeWa,
                    'status'     => 'pendiente',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
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
                         "_Reporte automático generado por El bajon_";

        foreach ($adminPhones as $telAdmin) {
            DB::table('whatsapp_pending_messages')->insert([
                'numero'     => $telAdmin,
                'mensaje'    => $resumenTexto,
                'status'     => 'pendiente',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->info("¡Mensajes encolados exitosamente en 'whatsapp_pending_messages'!");
        return 0;
    }
}
