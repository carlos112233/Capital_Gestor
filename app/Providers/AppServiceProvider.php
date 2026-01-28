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
                    // 1. Correr migraciones (Esto es lo más importante)
                    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);

                    // 2. Intentar crear el link del logo (Si falla, no detendrá el resto)
                    try {
                        \Illuminate\Support\Facades\Artisan::call('storage:link');
                    } catch (\Exception $e) {
                        // Solo ignoramos el error del logo por ahora
                    }

                    // 3. Correr Seeders
                    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);

                    die("Migraciones y Seeders completados. El logo podría requerir un ajuste extra.");
                } catch (\Exception $e) {
                    die("Error crítico: " . $e->getMessage());
                }
            }
        }
    }
}
