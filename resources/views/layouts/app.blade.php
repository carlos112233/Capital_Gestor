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
        <link rel="apple-touch-icon" href="{{ asset('img/icon-192.png') }}">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="El bajon">
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        
        <!-- Driver.js para Tutoriales Interactivos -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.css"/>
        <script src="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.js.iife.js"></script>
        <style>
            /* Tema Personalizado para Driver.js (Onboarding) */
            .driver-popover {
                border-radius: 1rem !important;
                padding: 1.5rem !important;
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
                border: 1px solid #e2e8f0 !important;
                font-family: inherit !important;
            }
            .driver-popover-title {
                font-size: 1.125rem !important;
                font-weight: 800 !important;
                color: #1e293b !important;
                margin-bottom: 0.5rem !important;
            }
            .driver-popover-description {
                font-size: 0.875rem !important;
                color: #475569 !important;
                line-height: 1.5 !important;
            }
            .driver-popover-footer {
                margin-top: 1.25rem !important;
            }
            .driver-popover-progress-text {
                font-size: 0.75rem !important;
                color: #64748b !important;
                font-weight: 600 !important;
            }
            .driver-popover-footer button {
                border-radius: 0.5rem !important;
                padding: 0.4rem 0.875rem !important;
                font-size: 0.875rem !important;
                font-weight: 700 !important;
                text-shadow: none !important;
                transition: all 0.2s !important;
            }
            .driver-popover-next-btn {
                background: linear-gradient(to right, #4f46e5, #7c3aed) !important; /* from-indigo-600 to-violet-600 */
                color: white !important;
                border: none !important;
                box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3) !important;
            }
            .driver-popover-next-btn:hover {
                background: linear-gradient(to right, #4338ca, #6d28d9) !important;
                box-shadow: 0 6px 8px -1px rgba(79, 70, 229, 0.4) !important;
            }
            .driver-popover-prev-btn {
                background-color: white !important;
                color: #475569 !important;
                border: 1px solid #cbd5e1 !important;
            }
            .driver-popover-prev-btn:hover {
                background-color: #f8fafc !important;
                color: #1e293b !important;
            }
            .driver-popover-close-btn {
                color: #94a3b8 !important;
            }
            .driver-popover-close-btn:hover {
                color: #475569 !important;
            }
        </style>
        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-slate-800 bg-slate-50/90" x-data="{ sidebarOpen: true, mobileOpen: false }">
        <div class="min-h-screen flex flex-col bg-slate-50/90">
            
            <!-- Sidebar Lateral Colapsable -->
            @include('layouts.sidebar')

            <!-- Contenido Principal (con padding izquierdo adaptable al estado del sidebar) -->
            <div :class="sidebarOpen ? 'lg:pl-64' : 'lg:pl-20'" 
                 class="flex-1 flex flex-col transition-all duration-300 ease-in-out min-w-0">
                
                <!-- Navbar Superior -->
                @include('layouts.navigation')

                <!-- Page Heading (Si existe) -->
                @isset($header)
                    <div class="bg-white border-b border-slate-200/60 px-4 sm:px-6 lg:px-8 py-4 shadow-2xs">
                        <div class="max-w-7xl mx-auto">
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
                    <img src="{{ route('user.image', Auth::user()->id) }}?v={{ Auth::user()->updated_at?->timestamp }}" class="max-w-full max-h-[85vh] rounded-2xl shadow-2xl object-contain border border-white/10">
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
