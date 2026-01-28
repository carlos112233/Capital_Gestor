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
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Tu código del comando secreto que ya tenías...
        if (!app()->runningInConsole()) {
            if (request()->has('comando_secreto')) {
                try {
                    // Crear tablas
                    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);

                    // Crear enlace de storage (para el Logo)
                    if (!file_exists(public_path('storage'))) {
                        \Illuminate\Support\Facades\Artisan::call('storage:link');
                    }

                    // Ejecutar Seeders
                    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);

                    die("Proceso completado: Migraciones, Storage Link y Seeders ejecutados con éxito.");
                } catch (\Exception $e) {
                    die("Error durante el proceso: " . $e->getMessage());
                }
            }
        }
    }
}
