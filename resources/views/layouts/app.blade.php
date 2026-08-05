<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'El rico bajon') }}</title>
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
        <link rel="icon" type="image/png" href="{{ asset('img/Logo.png') }}">
        <link rel="manifest" href="{{ asset('manifest.json') }}">
        <meta name="theme-color" content="#4f46e5">
        <link rel="apple-touch-icon" href="{{ asset('img/icon-192.png') }}">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="El rico bajon">
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

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

                Swal.fire({
                    title: '¿Estás seguro?',
                    text: `Se eliminará ${itemName}. ¡Esta acción no se puede deshacer!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed && form) {
                        delete form.dataset.submitting;
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
