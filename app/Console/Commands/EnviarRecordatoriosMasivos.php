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
                $mensaje = $controller->generarMensajeRecordatorio($user, 0);

                // Limpiar número
                $num = preg_replace('/[^0-9]/', '', $user->telefono);
                $num = (strlen($num) == 10) ? '521' . $num : $num;

                \Illuminate\Support\Facades\DB::table('whatsapp_pending_messages')->insert([
                    'numero' => $num,
                    'mensaje' => $mensaje,
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
