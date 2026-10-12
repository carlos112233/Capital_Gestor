<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'El bajon') }}</title>
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
        <link rel="icon" type="image/png" href="{{ asset('img/Logo.png') }}">
        <link rel="manifest" href="{{ asset('manifest.json') }}">
        <meta name="theme-color" content="#4f46e5">
        <link rel="apple-touch-icon" href="{{ asset('img/icon-192.png') }}">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="El bajon">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />

        <!-- Estilos Blindados (Tailwind + Fallbacks Directos) -->
        <style>
            html, body {
                margin: 0;
                padding: 0;
                min-height: 100%;
                background-color: #0c051f !important;
                color: #ffffff;
                font-family: 'Figtree', sans-serif;
            }
            .cosmic-wrapper {
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                position: relative;
                overflow: hidden;
                background: radial-gradient(circle at 50% 20%, #1e0b3c 0%, #0d041e 60%, #080214 100%) !important;
            }
            .cosmic-card {
                width: 100% !important;
                max-width: 420px !important;
                margin: 0 auto !important;
                background-color: #ffffff !important;
                border-radius: 2rem !important;
                box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.65) !important;
                border: 1px solid rgba(255, 255, 255, 0.5) !important;
                padding: 1.5rem 2rem !important;
                position: relative !important;
                z-index: 30 !important;
                color: #1e293b !important;
            }
            .cosmic-planet {
                position: absolute;
                left: 8%;
                top: 26%;
                width: 140px;
                height: 140px;
                border-radius: 50%;
                background: radial-gradient(circle at 32% 30%, #fef08a 0%, #f59e0b 45%, #b45309 75%, #451a03 100%);
                box-shadow: 0 0 45px rgba(245, 158, 11, 0.45);
                z-index: 10;
                pointer-events: none;
            }
            .cosmic-orbit {
                position: absolute;
                inset: -20px;
                border-radius: 50%;
                border: 1.5px solid rgba(255, 255, 255, 0.15);
                transform: rotate(-25deg) scaleY(0.4);
                pointer-events: none;
            }
            .cosmic-rocket {
                position: absolute;
                right: 7%;
                top: 20%;
                width: 220px;
                transform: rotate(15deg);
                z-index: 10;
                pointer-events: none;
            }
            .cosmic-dunes {
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                width: 100%;
                pointer-events: none;
                z-index: 5;
                line-height: 0;
            }
            .cosmic-sphere-sm {
                position: absolute;
                left: 22%;
                bottom: 120px;
                width: 32px;
                height: 32px;
                border-radius: 50%;
                background: radial-gradient(circle at 32% 30%, #fdba74 0%, #f97316 55%, #9a3412 100%);
                box-shadow: 0 4px 15px rgba(249, 115, 22, 0.4);
                z-index: 10;
                pointer-events: none;
            }
            .cosmic-sphere-lg {
                position: absolute;
                right: 6%;
                bottom: -40px;
                width: 180px;
                height: 180px;
                border-radius: 50%;
                background: radial-gradient(circle at 35% 30%, #e9d5ff 0%, #c084fc 25%, #9333ea 60%, #4c1d95 90%, #2e1065 100%);
                box-shadow: 0 20px 45px rgba(0, 0, 0, 0.6);
                z-index: 25;
                pointer-events: none;
            }
            .cosmic-meteor {
                position: absolute;
                top: 130px;
                left: 12%;
                width: 140px;
                height: 2px;
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.9), transparent);
                transform: rotate(32deg);
                box-shadow: 0 0 10px white;
                pointer-events: none;
            }
            @media (max-width: 900px) {
                .cosmic-planet { display: none; }
                .cosmic-rocket { display: none; }
                .cosmic-sphere-lg { display: none; }
                .cosmic-sphere-sm { display: none; }
            }
        </style>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-slate-800 bg-[#0c051f] selection:bg-violet-600 selection:text-white min-h-screen">
        <div class="cosmic-wrapper">
            
            <!-- ====== ELEMENTOS CÓSMICOS DE FONDO (PLANETA, COHETE, DUNAS, ESTRELLAS) ====== -->
            <div class="absolute inset-0 pointer-events-none overflow-hidden z-0">
                
                <!-- Resplandor estelar -->
                <div class="absolute -top-32 left-1/4 w-[550px] h-[550px] bg-violet-700/20 rounded-full blur-[130px]"></div>
                <div class="absolute top-1/3 -right-20 w-[450px] h-[450px] bg-purple-600/20 rounded-full blur-[110px]"></div>

                <!-- Estrellas y destellos titilantes -->
                <svg class="absolute inset-0 w-full h-full text-white/50" xmlns="http://www.w3.org/2000/svg">
                    <g class="text-white/80">
                        <path d="M120 180 Q120 190 110 190 Q120 190 120 200 Q120 190 130 190 Q120 190 120 180 Z" fill="currentColor" opacity="0.75"/>
                        <path d="M780 120 Q780 128 772 128 Q780 128 780 136 Q780 128 788 128 Q780 128 780 120 Z" fill="currentColor" opacity="0.65"/>
                        <path d="M340 620 Q340 628 332 628 Q340 628 340 636 Q340 628 348 628 Q340 628 340 620 Z" fill="currentColor" opacity="0.6"/>
                        <path d="M1120 320 Q1120 327 1113 327 Q1120 327 1120 334 Q1120 327 1127 327 Q1120 327 1120 320 Z" fill="currentColor" opacity="0.8"/>
                    </g>
                    <circle cx="80" cy="110" r="1.5" fill="white" opacity="0.6" />
                    <circle cx="210" cy="240" r="2" fill="white" opacity="0.85" />
                    <circle cx="280" cy="90" r="1.5" fill="#fde047" opacity="0.7" />
                    <circle cx="450" cy="150" r="1" fill="white" opacity="0.5" />
                    <circle cx="560" cy="80" r="2" fill="#c084fc" opacity="0.7" />
                    <circle cx="890" cy="190" r="1.5" fill="white" opacity="0.7" />
                    <circle cx="1020" cy="95" r="2.5" fill="#fde047" opacity="0.9" />
                    <circle cx="1250" cy="160" r="1.5" fill="white" opacity="0.6" />
                    <circle cx="1340" cy="280" r="2" fill="white" opacity="0.7" />
                </svg>

                <!-- Estrella fugaz (Meteor) -->
                <div class="cosmic-meteor"></div>

                <!-- PLANETA DORADO 3D (Izquierda) -->
                <div class="cosmic-planet">
                    <div class="cosmic-orbit"></div>
                </div>

                <!-- COHETE 3D VECTORIAL (Derecha ascendiendo) -->
                <div class="cosmic-rocket">
                    <svg viewBox="0 0 320 420" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto drop-shadow-[0_15px_30px_rgba(124,58,237,0.5)]">
                        <defs>
                            <linearGradient id="rocketBody" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#c084fc"/>
                                <stop offset="35%" stop-color="#9333ea"/>
                                <stop offset="80%" stop-color="#6b21a8"/>
                                <stop offset="100%" stop-color="#3b0764"/>
                            </linearGradient>
                            <linearGradient id="rocketHighlight" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" stop-color="#f3e8ff" stop-opacity="0.9"/>
                                <stop offset="100%" stop-color="#c084fc" stop-opacity="0.1"/>
                            </linearGradient>
                            <linearGradient id="rocketWingLeft" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#7e22ce"/>
                                <stop offset="100%" stop-color="#3b0764"/>
                            </linearGradient>
                            <linearGradient id="rocketWingRight" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#a855f7"/>
                                <stop offset="100%" stop-color="#581c87"/>
                            </linearGradient>
                            <linearGradient id="rocketFlame" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" stop-color="#ffffff"/>
                                <stop offset="20%" stop-color="#fef08a"/>
                                <stop offset="60%" stop-color="#f97316"/>
                                <stop offset="100%" stop-color="#dc2626" stop-opacity="0"/>
                            </linearGradient>
                            <radialGradient id="flameGlow" cx="50%" cy="50%" r="50%">
                                <stop offset="0%" stop-color="#fbbf24" stop-opacity="0.8"/>
                                <stop offset="100%" stop-color="#ea580c" stop-opacity="0"/>
                            </radialGradient>
                        </defs>

                        <!-- Fuego y propulsión -->
                        <ellipse cx="160" cy="340" rx="42" ry="50" fill="url(#flameGlow)"/>
                        <path d="M140 300 Q160 380 160 410 Q160 380 180 300 Z" fill="url(#rocketFlame)"/>
                        <path d="M148 300 Q160 360 160 385 Q160 360 172 300 Z" fill="#ffffff" opacity="0.9"/>

                        <!-- Tobera -->
                        <path d="M136 290 L184 290 L174 312 L146 312 Z" fill="#1e1b4b" stroke="#312e81" stroke-width="2"/>

                        <!-- Ala Izquierda -->
                        <path d="M132 180 L76 270 L132 284 Z" fill="url(#rocketWingLeft)"/>

                        <!-- Ala Derecha -->
                        <path d="M188 180 L244 270 L188 284 Z" fill="url(#rocketWingRight)"/>

                        <!-- Cuerpo del cohete -->
                        <path d="M160 50 C190 120 196 220 188 290 L132 290 C124 220 130 120 160 50 Z" fill="url(#rocketBody)"/>

                        <!-- Aleta dorsal -->
                        <path d="M160 90 L166 276 L160 286 L154 276 Z" fill="#c084fc" opacity="0.8"/>

                        <!-- Reflejo brillante -->
                        <path d="M152 75 C140 130 136 210 138 270 L146 270 C144 210 148 130 158 75 Z" fill="url(#rocketHighlight)"/>

                        <!-- Ventana / Cabina -->
                        <circle cx="160" cy="140" r="15" fill="#0f172a" stroke="#c084fc" stroke-width="3"/>
                        <circle cx="160" cy="140" r="11" fill="#38bdf8" opacity="0.9"/>
                        <path d="M154 133 Q162 131 166 137" stroke="#ffffff" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </div>

                <!-- DUNAS / PAISAJE DE CURVAS MORADAS EN LA BASE -->
                <div class="cosmic-dunes">
                    <svg viewBox="0 0 1440 380" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto min-h-[160px] sm:min-h-[240px]">
                        <path d="M0 240 C360 160 620 320 1000 210 C1220 145 1350 200 1440 220 L1440 380 L0 380 Z" fill="#1e0b3c" opacity="0.85"/>
                        <path d="M0 270 C280 200 680 340 1050 240 C1280 180 1380 230 1440 250 L1440 380 L0 380 Z" fill="#331163" opacity="0.9"/>
                        <defs>
                            <linearGradient id="frontDune" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#4c1d95"/>
                                <stop offset="50%" stop-color="#581c87"/>
                                <stop offset="100%" stop-color="#2e1065"/>
                            </linearGradient>
                        </defs>
                        <path d="M0 300 C320 240 540 360 880 280 C1140 220 1340 290 1440 305 L1440 380 L0 380 Z" fill="url(#frontDune)"/>
                    </svg>
                </div>

                <!-- ESFERAS 3D FLOTANTES -->
                <div class="cosmic-sphere-sm"></div>
                <div class="cosmic-sphere-lg">
                    <div style="position: absolute; top: 20px; left: 28px; width: 32px; height: 20px; border-radius: 50%; background: rgba(255, 255, 255, 0.7); filter: blur(3px); transform: rotate(-25deg);"></div>
                </div>
            </div>

            <!-- ====== HEADER SUPERIOR ====== -->
            <header class="relative z-20 w-full px-6 sm:px-10 py-3.5 flex items-center justify-between" style="padding-top: 0.85rem; padding-bottom: 0.5rem;">
                <!-- Marca Institucional Izquierda: Logo y Texto unidos -->
                <a href="/" class="inline-flex items-center gap-3 group text-decoration-none" style="display: inline-flex; align-items: center; gap: 0.75rem; text-decoration: none;">
                    <!-- Logo oficial compacto y nítido -->
                    <div style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <img src="{{ file_exists(public_path('img/Logo.svg')) ? asset('img/Logo.svg') : asset('img/Logo.png') }}?v={{ time() }}" 
                             style="width: 100%; height: 100%; object-fit: contain; display: block;" 
                             alt="El bajon Logo">
                    </div>

                    <!-- Texto de la Marca perfectamente agrupado junto al logo -->
                    <div style="display: flex; flex-direction: column; justify-content: center;">
                        <span class="text-white font-black tracking-wider text-base uppercase leading-tight" style="color: #ffffff; font-weight: 900; letter-spacing: 0.05em; font-size: 1rem; text-transform: uppercase; line-height: 1.1; margin: 0;">
                            EL BAJÓN
                        </span>
                        <span class="text-amber-400 font-extrabold text-[10px] tracking-widest uppercase mt-0.5" style="color: #fbbf24; font-weight: 800; font-size: 9.5px; letter-spacing: 0.12em; text-transform: uppercase; margin-top: 2px; line-height: 1;">
                            HAMBURGUESAS &amp; PANADERÍA
                        </span>
                    </div>
                </a>

                <!-- Botón de Menú Hamburguesa Derecho -->
                <button type="button" 
                        class="p-2 rounded-xl text-white/80 hover:text-white hover:bg-white/10 transition-colors focus:outline-none"
                        style="padding: 0.5rem; color: rgba(255, 255, 255, 0.8); background: transparent; border: none; cursor: pointer;"
                        aria-label="Menú">
                    <svg class="w-6 h-6" style="width: 1.5rem; height: 1.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </header>

            <!-- ====== CONTENIDO PRINCIPAL (TARJETA CENTRADA CON MAX-WIDTH BLINDADO) ====== -->
            <main class="flex-1 flex items-center justify-center px-4 py-4 relative z-30" style="display: flex; align-items: center; justify-content: center; padding-top: 0.5rem; padding-bottom: 0.5rem;">
                <div class="cosmic-card">
                    {{ $slot }}
                </div>
            </main>

            <!-- ====== FOOTER INFERIOR ====== -->
            <footer class="relative z-20 w-full px-6 sm:px-10 py-3 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs font-medium text-white/50" style="padding-top: 0.5rem; padding-bottom: 0.75rem;">
                <!-- Redes sociales -->
                <div class="flex items-center gap-2.5">
                    <a href="#" class="w-7 h-7 rounded-full bg-white/10 hover:bg-white/20 text-white/70 hover:text-white flex items-center justify-center transition-colors">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>
                    <a href="#" class="w-7 h-7 rounded-full bg-white/10 hover:bg-white/20 text-white/70 hover:text-white flex items-center justify-center transition-colors">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>
                    <a href="#" class="w-7 h-7 rounded-full bg-white/10 hover:bg-white/20 text-white/70 hover:text-white flex items-center justify-center transition-colors">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                    </a>
                </div>

                <!-- Copyright -->
                <div class="text-center text-slate-400">
                    &copy; {{ date('Y') }} El bajon &bull; Todos los derechos reservados
                </div>

                <!-- Versión y Región -->
                <div class="text-slate-400 font-mono text-[11px]">
                    v2.4.0 &bull; México
                </div>
            </footer>
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
