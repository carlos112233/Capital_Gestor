<?php

namespace App\Providers;


use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Artisan; // <-- Agrega esta línea
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // Tu código del comando secreto que ya tenías...
        if (isset($_GET['comando_secreto'])) {
            Artisan::call('migrate', ['--force' => true]);
            // AGREGAMOS ESTA LÍNEA PARA LOS SEEDERS:
            Artisan::call('db:seed', ['--force' => true]);

            dump("Migración y Seeders ejecutados con éxito");
        }
    }
}
