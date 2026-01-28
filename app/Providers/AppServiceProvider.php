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
                    // 'migrate:fresh' borra todo y reinstala de cero. 
                    // Es lo mejor para arreglar errores de seeders duplicados.
                    \Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--force' => true]);

                    // Ejecutar Seeders
                    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);

                    die("¡ÉXITO TOTAL! Base de datos limpiada, migrada y con datos iniciales.");
                } catch (\Exception $e) {
                    die("Error crítico: " . $e->getMessage());
                }
            }
        }
    }
}
