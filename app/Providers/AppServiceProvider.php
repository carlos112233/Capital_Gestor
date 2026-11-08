<?php

namespace App\Providers;


use Illuminate\Support\ServiceProvider;
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

        // Inyección global de configuración de marca y personalización (con caché)
        \Illuminate\Support\Facades\View::composer('*', function ($view) {
            try {
                $appSettings = \App\Models\AppSetting::getSettings();
            } catch (\Throwable $e) {
                $appSettings = \App\Models\AppSetting::createDefaultInstance();
            }
            $view->with('appSettings', $appSettings);
        });
    }
}
