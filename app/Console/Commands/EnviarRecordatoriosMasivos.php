<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class EnviarRecordatoriosMasivos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:recordatorios';
    protected $description = 'Envía recordatorios masivos de adeudo a todos los usuarios que tengan saldo pendiente.';

    public function handle()
    {
        $this->info('Iniciando envío masivo de recordatorios...');

        // Obtener usuarios que NO son admin
        $usuarios = \App\Models\User::whereDoesntHave('roles', function ($q) {
            $q->where('name', 'admin');
        })->with(['ventas.articulo', 'entradas'])->get();

        $controller = new \App\Http\Controllers\DashboardController();
        $count = 0;

        foreach ($usuarios as $user) {
            if (!$user->telefono) continue;

            $saldo = $user->saldo_pendiente; // Accessor

            // Solo enviar a los que tengan deuda mayor a 0
            if ($saldo > 0) {
                // Generar PDF con el detalle de compras / estado de cuenta
                $pdfPath = null;
                try {
                    $pdfPath = \App\Services\PdfReceiptService::generateEstadoCuentaPdf($user, 0);
                } catch (\Exception $ePdf) {
                    \Illuminate\Support\Facades\Log::error("Error generando PDF de estado de cuenta para Usuario #{$user->id}: " . $ePdf->getMessage());
                }

                $saldoFormat = number_format($saldo, 2);
                $mensaje = "Hola *{$user->name}*, te compartimos tu Estado de Cuenta adjunto con el saldo a cubrir de *\${$saldoFormat}*.\n\nTransferencia Bancaria BBVA Acuunt: 0123 4567 8901 2345 6789\nMercado Pago User: El Bajon Pagos\n\nFavor de enviar tu comprobante de pago a este número de WhatsApp. ¡Gracias!";

                // Limpiar número
                $num = preg_replace('/[^0-9]/', '', $user->telefono);
                $num = (strlen($num) == 10) ? '521' . $num : $num;

                \Illuminate\Support\Facades\DB::table('whatsapp_pending_messages')->insert([
                    'numero' => $num,
                    'mensaje' => $mensaje,
                    'pdf_path' => $pdfPath,
                    'status' => 'pendiente',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $count++;
            }
        }

        $this->info("Recordatorios generados exitosamente para {$count} usuarios.");
    }
}
