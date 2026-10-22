<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Aplica cabeceras HTTP de protección y hardening a todas las respuestas de la aplicación.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Protección contra Clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Protección contra MIME-sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Política de Referrer para evitar fuga de tokens en URL
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Restricción de capacidades del navegador
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Content Security Policy básico y seguro
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self' https: data: 'unsafe-inline' 'unsafe-eval'; img-src 'self' data: https: blob:; font-src 'self' https: data:;"
        );

        return $response;
    }
}

