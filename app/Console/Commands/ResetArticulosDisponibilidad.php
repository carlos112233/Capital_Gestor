<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetArticulosDisponibilidad extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'articulos:reset-disponibilidad';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reinicia el stock a 0 y la disponibilidad a falso de todos los artículos (Cierre 7:00 PM)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando reseteo de stock a 0 de artículos (7:00 PM)...');
        
        $affected = DB::table('articulos')
            ->where('nombre', '!=', 'Pago saldado')
            ->update([
                'stock' => 0,
                'disponible' => false
            ]);
        
        $this->info("Se ha actualizado el stock a 0 y estado 'No Disponible' de {$affected} artículos.");
        \Illuminate\Support\Facades\Log::info("Comando articulos:reset-disponibilidad ejecutado a las 7:00 PM. Artículos en 0: {$affected}.");
    }
}
