<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ ($appSettings->theme_mode ?? 'light') === 'dark' ? 'dark' : '' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <!-- Anti-FOUC Dark Mode & Theme Evaluation Script -->
        <script>
            (function() {
                const themeMode = '{{ $appSettings->theme_mode ?? 'light' }}';
                if (themeMode === 'dark' || (themeMode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                } else if (themeMode === 'light') {
                    document.documentElement.classList.remove('dark');
                }
            })();
        </script>

        <title>{{ $appSettings->app_name ?? config('app.name', 'El Bajón') }}</title>
        <link rel="icon" type="image/x-icon" href="{{ $appSettings->favicon_url ?? asset('favicon.ico') }}">
        <link rel="icon" type="image/png" href="{{ $appSettings->favicon_url ?? asset('favicon.ico') }}">
        <link rel="manifest" href="{{ asset('manifest.json') }}">
        <meta name="theme-color" content="{{ $appSettings->primary_color ?? '#6366f1' }}">
        <link rel="apple-touch-icon" href="{{ $appSettings->logo_url ?? asset('img/icon-192.png') }}">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="{{ $appSettings->app_name ?? 'El Bajón' }}">
        
        <!-- Tipografías Dinámicas (Google Fonts) -->
        @php
            $fontFamily = $appSettings->font_family ?? 'Inter';
            $fontQuery = match ($fontFamily) {
                'Poppins' => 'family=Poppins:wght@300;400;500;600;700;800',
                'Roboto' => 'family=Roboto:wght@300;400;500;700;900',
                'Plus Jakarta Sans' => 'family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800',
                'Montserrat' => 'family=Montserrat:wght@300;400;500;600;700;800',
                default => 'family=Inter:wght@300;400;500;600;700;800',
            };
        @endphp
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?{{ $fontQuery }}&display=swap" rel="stylesheet">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        
        <!-- Driver.js para Tutoriales Interactivos -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.css"/>
        <script src="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.js.iife.js"></script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            :root {
                --color-sidebar: {{ $appSettings->sidebar_color ?? '#0f172a' }};
                --color-primary: {{ $appSettings->primary_color ?? '#6366f1' }};
                --color-secondary: {{ $appSettings->secondary_color ?? '#10b981' }};
                --font-family: '{{ $appSettings->font_family ?? 'Inter' }}', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
            #sidebar, #sidebar-mobile {
                background-color: var(--color-sidebar, #0f172a) !important;
            }
            body {
                font-family: var(--font-family) !important;
            }
            .bg-sidebar-custom {
                background-color: var(--color-sidebar, #0f172a) !important;
            }
            .btn-brand-primary {
                background-color: var(--color-primary) !important;
                color: #ffffff !important;
            }
            .btn-brand-primary:hover {
                filter: brightness(0.92);
            }
            .btn-brand-secondary {
                background-color: var(--color-secondary) !important;
                color: #ffffff !important;
            }
            .text-brand-primary {
                color: var(--color-primary) !important;
            }
            .text-brand-secondary {
                color: var(--color-secondary) !important;
            }
            .border-brand-primary {
                border-color: var(--color-primary) !important;
            }
            .border-brand-secondary {
                border-color: var(--color-secondary) !important;
            }
            /* Tema Personalizado para Driver.js (Onboarding) - Adaptado al color primario */
            .driver-popover-next-btn {
                background: var(--color-primary) !important;
                color: white !important;
                border: none !important;
                text-shadow: none !important;
            }
            .driver-popover-next-btn:hover {
                filter: brightness(0.9);
            }

            /* ==========================================================================
               SISTEMA GLOBAL DE ALTO CONTRASTE - MODO OSCURO (WCAG AAA)
               ========================================================================== */

            /* 1. CAPA 0: Fondo General Profundo */
            html.dark, html.dark body {
                background-color: #0b1120 !important;
                color: #f8fafc !important;
            }

            /* 2. CAPA 1: Tarjetas, Tablas y Contenedores con Borde de Separación */
            html.dark .bg-white,
            html.dark [class*="bg-white"],
            html.dark .bg-slate-50,
            html.dark [class*="bg-slate-50"],
            html.dark .bg-gray-50,
            html.dark [class*="bg-gray-50"],
            html.dark .bg-slate-100,
            html.dark [class*="bg-slate-100"],
            html.dark .bg-gray-100,
            html.dark [class*="bg-gray-100"],
            html.dark .bg-slate-900\/50,
            html.dark .bg-slate-800 {
                background-color: #161f32 !important;
                border: 1px solid #334155 !important;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.4), 0 2px 4px -2px rgba(0, 0, 0, 0.3) !important;
            }

            /* 3. CAPA 2: Cabeceras de Tabla y Zonas de Búsqueda */
            html.dark thead, 
            html.dark thead tr,
            html.dark th {
                background-color: #1e293b !important;
                color: #cbd5e1 !important; /* Gris claro de alto contraste */
                font-weight: 700 !important;
                border-bottom: 2px solid #475569 !important;
                text-transform: uppercase !important;
                letter-spacing: 0.05em !important;
            }

            /* Pie de Tabla y Sumatorias */
            html.dark tfoot,
            html.dark tfoot tr,
            html.dark [class*="sumatoria"],
            html.dark [id*="sumatoria"] {
                background-color: #161f32 !important;
                color: #f1f5f9 !important;
                border-color: #334155 !important;
            }

            /* Filas alternadas para lectura descansada */
            html.dark tbody tr:hover {
                background-color: #1e2d44 !important;
            }
            html.dark td {
                border-bottom: 1px solid #1e293b !important;
                color: #e2e8f0 !important;
            }

            /* 4. TÍTULOS Y TEXTOS INMUTABLES */
            html.dark h1, html.dark h2, html.dark h3, html.dark h4, 
            html.dark .text-gray-900, html.dark .text-slate-900 {
                color: #ffffff !important;
                font-weight: 800 !important;
            }

            /* Textos secundarios legibles (Nunca más grises oscuros invisibles) */
            html.dark p, html.dark span, html.dark label,
            html.dark .text-gray-500, html.dark .text-slate-500 {
                color: #cbd5e1 !important;
            }

            /* Botones y acentos preservan su color de texto original */
            html.dark button span,
            html.dark a.btn-brand-primary span,
            html.dark .text-white {
                color: inherit !important;
            }

            /* 5. INPUTS Y BUSCADORES DE ALTO CONTRASTE */
            html.dark input, 
            html.dark select, 
            html.dark textarea {
                background-color: #0d1527 !important;
                color: #ffffff !important;
                border: 1px solid #475569 !important;
            }
            html.dark input::placeholder,
            html.dark textarea::placeholder {
                color: #94a3b8 !important;
            }

            /* 6. ESTADOS VACÍOS ("Utiliza el buscador...") */
            html.dark .bg-slate-100,
            html.dark .bg-gray-50 {
                background-color: #161f32 !important;
                color: #94a3b8 !important;
                border-color: #334155 !important;
            }

            /* 7. IMPORTES FINANCIEROS INTOCABLES EN MODO OSCURO */
            html.dark .text-red-600,
            html.dark .text-red-500,
            html.dark [class*="text-red-"] {
                color: #ff4d4d !important; /* Rojo neón intenso */
                font-weight: 800 !important;
            }

            html.dark .text-green-600,
            html.dark .text-green-500,
            html.dark .text-emerald-600,
            html.dark .text-emerald-500,
            html.dark [class*="text-green-"],
            html.dark [class*="text-emerald-"] {
                color: #10b981 !important; /* Verde esmeralda luminoso */
                font-weight: 800 !important;
            }
        </style>
    </head>
    <body class="font-sans antialiased text-slate-800 dark:text-slate-100 bg-slate-50/90 dark:bg-slate-950 transition-colors duration-200" x-data="{ sidebarOpen: true, mobileOpen: false }">
        <div class="min-h-screen flex flex-col bg-slate-50/90 dark:bg-slate-950">
            
            <!-- Sidebar Lateral Colapsable -->
            @include('layouts.sidebar')

            <!-- Contenido Principal (con padding izquierdo adaptable al estado del sidebar) -->
            <div :class="sidebarOpen ? 'lg:pl-64' : 'lg:pl-20'" 
                 class="flex-1 flex flex-col transition-all duration-300 ease-in-out min-w-0">
                
                <!-- Navbar Superior -->
                @include('layouts.navigation')

                <!-- Page Heading (Si existe) -->
                @isset($header)
                    <div class="bg-white dark:bg-slate-900 border-b border-slate-200/60 dark:border-slate-800 px-4 sm:px-6 lg:px-8 py-4 shadow-2xs transition-colors">
                        <div class="max-w-7xl mx-auto text-slate-800 dark:text-slate-100">
                            {{ $header }}
                        </div>
                    </div>
                @endisset

                <!-- Page Content -->
                <main class="flex-1 px-4 sm:px-6 lg:px-8 py-6 max-w-7xl w-full mx-auto">
                    {{ $slot }}
                </main>
            </div>
        </div>

        <!-- Modal Foto de Perfil (Global) que escucha el evento -->
        @if(Auth::check() && Auth::user()->image)
            <div x-data="{ openGlobalPhotoModal: false }" @open-global-photo-modal.window="openGlobalPhotoModal = true" x-show="openGlobalPhotoModal" style="display: none;" x-transition class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/80 backdrop-blur-sm p-4">
                <div @click.away="openGlobalPhotoModal = false" class="relative w-full max-w-3xl flex justify-center items-center">
                    <button @click="openGlobalPhotoModal = false" class="absolute -top-12 right-0 md:-right-8 md:-top-8 text-white hover:text-slate-300 focus:outline-none bg-white/10 p-2 rounded-full backdrop-blur-md">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                    <img src="{{ Auth::user()->image_url }}" onerror="this.onerror=null; this.src='{{ asset('img/default_user.svg') }}';" class="max-w-full max-h-[85vh] rounded-2xl shadow-2xl object-contain border border-white/10" alt="{{ Auth::user()->name }}">
                </div>
            </div>
        @endif

        <!-- SweetAlert2 -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                @if (session('success'))
                    Swal.fire({
                        icon: 'success',
                        title: '¡Operación Exitosa!',
                        text: "{{ session('success') }}",
                        timer: 3000,
                        showConfirmButton: false
                    });
                @endif

                @if (session('error'))
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: "{{ session('error') }}",
                        confirmButtonColor: '#ef4444'
                    });
                @endif

                @if (session('info'))
                    Swal.fire({
                        icon: 'info',
                        title: 'Información',
                        text: "{{ session('info') }}",
                        confirmButtonColor: '#3b82f6'
                    });
                @endif
            });

            window.openModal = function(name) {
                window.dispatchEvent(new CustomEvent('open-modal', { detail: name }));
            };
            window.closeModal = function(name) {
                window.dispatchEvent(new CustomEvent('close-modal', { detail: name }));
            };

            // --- PROTECCIÓN GLOBAL CONTRA DOBLE ENVÍO DE FORMULARIOS (ANTI DOUBLE-SUBMIT) ---
            document.addEventListener('submit', function(e) {
                const form = e.target;
                if (!form || form.tagName !== 'FORM') return;

                // Si el formulario requiere confirmación de borrado, dejar que confirmDelete maneje el envío
                if (form.getAttribute('onsubmit') && form.getAttribute('onsubmit').includes('return confirmDelete')) {
                    return;
                }

                if (form.dataset.submitting === 'true') {
                    e.preventDefault();
                    return false;
                }

                form.dataset.submitting = 'true';
                const submitBtns = form.querySelectorAll('button[type="submit"], input[type="submit"]');
                submitBtns.forEach(btn => {
                    btn.disabled = true;
                    btn.classList.add('opacity-60', 'cursor-not-allowed', 'pointer-events-none');
                });
            }, true);

            const originalSubmit = HTMLFormElement.prototype.submit;
            HTMLFormElement.prototype.submit = function() {
                if (this.dataset.submitting === 'true') {
                    return false;
                }
                this.dataset.submitting = 'true';
                const submitBtns = this.querySelectorAll('button[type="submit"], input[type="submit"]');
                submitBtns.forEach(btn => {
                    btn.disabled = true;
                    btn.classList.add('opacity-60', 'cursor-not-allowed', 'pointer-events-none');
                });
                return originalSubmit.apply(this, arguments);
            };

            function confirmDelete(formOrId, itemName = 'este registro') {
                const form = typeof formOrId === 'string' ? document.getElementById(formOrId) : formOrId;
                const isEntrada = itemName.toLowerCase().includes('entrada') || (form && form.action && form.action.includes('entradas'));

                let htmlContent = `Se eliminará ${itemName}. ¡Esta acción no se puede deshacer!`;
                if (isEntrada) {
                    htmlContent += `
                        <div class="mt-4 text-left border-t border-slate-200 pt-3">
                            <label class="inline-flex items-center text-sm text-slate-700 font-medium cursor-pointer">
                                <input type="checkbox" id="swal-enviar-wa" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 mr-2">
                                <span>Enviar notificación por WhatsApp al cliente informando que su pago fue revertido</span>
                            </label>
                        </div>
                    `;
                }

                Swal.fire({
                    title: '¿Estás seguro?',
                    html: htmlContent,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed && form) {
                        delete form.dataset.submitting;
                        
                        if (isEntrada) {
                            const waCheckbox = document.getElementById('swal-enviar-wa');
                            let hiddenWaInput = form.querySelector('input[name="enviar_wa"]');
                            if (!hiddenWaInput) {
                                hiddenWaInput = document.createElement('input');
                                hiddenWaInput.type = 'hidden';
                                hiddenWaInput.name = 'enviar_wa';
                                form.appendChild(hiddenWaInput);
                            }
                            hiddenWaInput.value = (waCheckbox && waCheckbox.checked) ? '1' : '0';
                        }

                        const submitBtns = form.querySelectorAll('button[type="submit"], input[type="submit"]');
                        submitBtns.forEach(btn => {
                            btn.disabled = false;
                            btn.classList.remove('opacity-60', 'cursor-not-allowed', 'pointer-events-none');
                        });
                        if (typeof originalSubmit === 'function') {
                            originalSubmit.call(form);
                        } else {
                            form.submit();
                        }
                    }
                });
                return false;
            }
        </script>
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/sw.js')
                        .then(reg => console.log('SW registrado con éxito:', reg.scope))
                        .catch(err => console.log('Error registrando SW:', err));
                });
            }

            // Inicialización Global de TomSelect para todos los selects con clase .searchable-select
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.searchable-select').forEach(function(select) {
                    if (!select.tomselect) {
                        new TomSelect(select, {
                            create: false,
                            placeholder: select.getAttribute('placeholder') || "Buscar opción..."
                        });
                    }
                });
            });
        </script>
    </body>
</html>
