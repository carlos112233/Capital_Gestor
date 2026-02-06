<?php

namespace App\Providers;


use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Artisan; // <-- Agrega esta línea
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;
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
        Carbon::setLocale('es');
        date_default_timezone_set('America/Mexico_City');

        if (config('app.env') === 'production') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        if (!app()->runningInConsole() && request()->has('comando_secreto')) {
            try {
                // LIMPIEZA DE CACHÉ (Vital para que el correo funcione)
                \Illuminate\Support\Facades\Artisan::call('config:clear');
                \Illuminate\Support\Facades\Artisan::call('cache:clear');
                \Illuminate\Support\Facades\Artisan::call('route:clear');
                \Illuminate\Support\Facades\Artisan::call('view:clear');

                // MIGRACIONES (Solo por si acaso)
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);

                // STORAGE LINK (Para el logo)
                if (!file_exists(public_path('storage'))) {
                    \Illuminate\Support\Facades\Artisan::call('storage:link');
                }

                die("Caché limpiada y Sistema actualizado con éxito. Intenta enviar el correo ahora.");
            } catch (\Exception $e) {
                die("Error durante el comando: " . $e->getMessage());
            }
        }
    }
}
