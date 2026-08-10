<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'El bajon') }}</title>
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
        <link rel="icon" type="image/png" href="{{ asset('img/Logo.png') }}">
        <link rel="manifest" href="{{ asset('manifest.json') }}">
        <meta name="theme-color" content="#4f46e5">

        <!-- Meta Tags para PWA -->
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="El bajon">
        
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-inter antialiased bg-slate-50 dark:bg-[#0B0F19] selection:bg-indigo-500 selection:text-white">
        <!-- Partículas de fondo animadas -->
        <div class="fixed inset-0 z-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-[20%] -left-[10%] w-[50%] h-[50%] rounded-full bg-indigo-500/10 blur-[100px] animate-pulse-slow"></div>
            <div class="absolute top-[20%] -right-[10%] w-[40%] h-[60%] rounded-full bg-violet-500/10 blur-[120px] animate-pulse-slow" style="animation-delay: 2s;"></div>
            <div class="absolute -bottom-[20%] left-[20%] w-[60%] h-[40%] rounded-full bg-fuchsia-500/10 blur-[100px] animate-pulse-slow" style="animation-delay: 4s;"></div>
        </div>

        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 relative z-10">
            <!-- Logo con efecto de levitación -->
            <div class="mb-8 transform hover:scale-105 transition-all duration-300 animate-float">
                <a href="/" class="flex flex-col items-center gap-4 group">
                    <div class="relative p-4 rounded-2xl bg-white/5 backdrop-blur-sm border border-white/10 shadow-2xl">
                        <div class="absolute inset-0 bg-gradient-to-tr from-indigo-500/20 to-purple-500/20 rounded-2xl blur-md group-hover:blur-lg transition-all"></div>
                        <img src="{{ asset('img/Logo.png') }}" 
                         class="h-24 w-auto relative z-10 drop-shadow-xl" 
                         alt="El bajon Logo">
                    </div>
                </a>
            </div>

            <!-- Contenedor Glassmorphism para el contenido -->
            <div class="w-full sm:max-w-md mt-2 px-8 py-10 bg-white/80 dark:bg-slate-900/60 backdrop-blur-xl shadow-[0_8px_30px_rgb(0,0,0,0.12)] dark:shadow-[0_8px_30px_rgb(0,0,0,0.3)] sm:rounded-3xl border border-slate-200/50 dark:border-white/10 relative overflow-hidden">
                <!-- Brillo superior -->
                <div class="absolute top-0 left-0 right-0 h-[1px] bg-gradient-to-r from-transparent via-indigo-500/50 to-transparent"></div>
                
                {{ $slot }}
            </div>

            <!-- Footer minimalista -->
            <div class="mt-8 text-sm text-slate-500 dark:text-slate-400">
                &copy; {{ date('Y') }} El bajon &bull; Todos los derechos reservados
            </div>
        </div>
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/sw.js')
                        .then(reg => console.log('SW registrado con éxito:', reg.scope))
                        .catch(err => console.log('Error registrando SW:', err));
                });
            }
        </script>
    </body>
</html>
