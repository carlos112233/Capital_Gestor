<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        api: __DIR__.'/../routes/api.php', // <
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Registramos el alias para que Laravel entienda qué es 'role'
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);

        // Aprovechamos para configurar los proxies de Render (evita errores de HTTPS)
        $middleware->trustProxies(at: '*');

        // Redirección para usuarios NO autenticados que intentan acceder a rutas protegidas
        $middleware->redirectGuestsTo('/login');

        // Redirección para usuarios SÍ autenticados que intentan acceder al login/registro
        $middleware->redirectUsersTo(function () {
            if (auth()->check() && auth()->user()->hasRole('admin')) {
                return route('dashboardAdmin');
            }
            return route('dashboard');
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
