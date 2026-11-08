<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MantenimientoCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:mantenimiento-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia cachés, sincroniza storage y relanza migraciones en mantenimiento controlado.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Reiniciando cachés del sistema...');
        $this->call('config:clear');
        $this->call('cache:clear');
        $this->call('route:clear');
        $this->call('view:clear');

        $this->info('Verificando storage del sistema...');
        if (!file_exists(public_path('storage'))) {
            $this->call('storage:link');
        }

        $this->comment('Mantenimiento de caché finalizado sin ejecución desde peticiones web.');

        return self::SUCCESS;
    }
}
