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
    protected $description = 'Reinicia la disponibilidad de todos los artículos a falso (no disponible)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando reseteo de disponibilidad de artículos...');
        
        $affected = DB::table('articulos')->update(['disponible' => false]);
        
        $this->info("Se han actualizado {$affected} artículos a 'No Disponible'.");
        \Illuminate\Support\Facades\Log::info("Comando articulos:reset-disponibilidad ejecutado. Artículos reseteados: {$affected}.");
    }
}
