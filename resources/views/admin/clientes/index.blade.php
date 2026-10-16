<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                {{ __('Gestión de Usuarios') }}
            </h2>
            <div class="flex items-center gap-2.5">
                <button type="button" onclick="window.iniciarTourUsuarios()"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold text-xs border border-slate-200 dark:border-slate-700 shadow-xs transition-all cursor-pointer"
                    title="Ver guía interactiva de este módulo">
                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Guía Interactiva</span>
                </button>
                <button id="tour-btn-nuevo-usuario" type="button" onclick="openModal('create-cliente')"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-sm shadow-md shadow-indigo-500/25 hover:shadow-lg hover:shadow-indigo-500/35 transition-all duration-200 transform hover:-translate-y-0.5 focus:outline-none cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('Nuevo Usuario') }}
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form method="GET" action="{{ route('admin.clientes.index') }}" class="mb-5">
                <div id="tour-buscador-usuarios" class="relative w-full">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" name="q" id="q" value="{{ request('q') }}"
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm text-slate-800 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:bg-white dark:focus:bg-slate-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all duration-200 shadow-sm"
                        placeholder="Buscar usuario por nombre, correo o teléfono..." autocomplete="off" />
                </div>
            </form>

            <div class="bg-white dark:bg-slate-800 overflow-hidden shadow-sm sm:rounded-lg border border-slate-200 dark:border-slate-700">
                <div class="overflow-x-auto">
                    <div id="contenedor-tabla" class="mt-4 overflow-x-auto">
                        @include('admin.clientes._tabla', ['clientes' => $clientes])
                    </div>
                </div>
                <div class="p-4">
                    {{ $clientes->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nuevo Usuario -->
    <x-modal name="create-cliente">
        <div class="p-6 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100">
            <div class="flex justify-between items-center pb-3 border-b border-slate-200 dark:border-slate-700 mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Registrar Nuevo Usuario</h3>
                <button type="button" onclick="closeModal('create-cliente')" class="text-gray-400 hover:text-gray-600 dark:hover:text-white font-bold text-xl cursor-pointer">&times;</button>
            </div>
            <form method="POST" action="{{ route('admin.clientes.store') }}" enctype="multipart/form-data">
                @csrf
                @include('admin.clientes._form', ['cliente' => new \App\Models\User()])
            </form>
        </div>
    </x-modal>
</x-app-layout>

@php
    $hasSeenTutorialUsuarios = Auth::check() && Auth::user()->tutorials()->where('tutorial_name', 'usuarios')->exists();
@endphp

<script>
    const buscador = document.getElementById('q');
    let timeout = null;

    buscador.addEventListener('keyup', function() {
        clearTimeout(timeout); // Limpiar búsqueda anterior
        const query = this.value;

        // Esperar 300ms después de dejar de escribir
        timeout = setTimeout(() => {
            fetch("{{ route('admin.clientes.index') }}?q=" + encodeURIComponent(query), {
                    headers: {
                        "X-Requested-With": "XMLHttpRequest"
                    }
                })
                .then(res => res.text())
                .then(html => {
                    document.getElementById('contenedor-tabla').innerHTML = html;
                });
        }, 300);
    });

    window.iniciarTourUsuarios = function() {
        if (!window.driver || !window.driver.js) return;
        const driverObj = window.driver.js.driver({
            showProgress: true,
            nextBtnText: 'Siguiente ➔',
            prevBtnText: '⬅ Anterior',
            doneBtnText: '¡Entendido!',
            steps: [
                {
                    element: '#tour-btn-nuevo-usuario',
                    popover: {
                        title: 'Registrar Nuevo Usuario',
                        description: 'Haz clic aquí para dar de alta cualquier cuenta en el sistema. En el formulario podrás seleccionar su Rol Oficial (Cliente, Vendedor, Cocina o Administrador) y activar el envío automático de credenciales por WhatsApp.',
                        side: 'bottom',
                        align: 'end'
                    }
                },
                {
                    element: '#tour-buscador-usuarios',
                    popover: {
                        title: 'Búsqueda Instantánea',
                        description: 'Filtra usuarios en tiempo real escribiendo su nombre, correo electrónico o número de teléfono.',
                        side: 'bottom',
                        align: 'start'
                    }
                },
                {
                    element: '#tour-col-rol',
                    popover: {
                        title: 'Distintivo Visual de Rol',
                        description: 'Cada cuenta muestra su rol asignado con un código de color de alto contraste: Púrpura (Admin), Azul (Vendedor), Naranja (Cocina) y Esmeralda (Cliente).',
                        side: 'top',
                        align: 'center'
                    }
                },
                {
                    element: '#tour-col-acciones',
                    popover: {
                        title: 'Credenciales por WhatsApp y Edición',
                        description: 'Usa el botón verde de WhatsApp para enviar o restablecer la contraseña del usuario al instante, el lápiz para cambiar su información o rol, y el botón de estado para activar o desactivar su acceso.',
                        side: 'left',
                        align: 'center'
                    }
                }
            ],
            onDestroyStarted: () => {
                driverObj.destroy();
                fetch('{{ route("tutorial.marcar-visto") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ tutorial_name: 'usuarios' })
                }).catch(() => {});
            }
        });
        driverObj.drive();
    };

    document.addEventListener('DOMContentLoaded', function() {
        const hasSeen = @json($hasSeenTutorialUsuarios);
        const forceTutorial = new URLSearchParams(window.location.search).get('tutorial') === 'true';

        if (!hasSeen || forceTutorial) {
            if (forceTutorial) {
                const url = new URL(window.location);
                url.searchParams.delete('tutorial');
                window.history.replaceState({}, '', url);
            }
            setTimeout(() => {
                window.iniciarTourUsuarios();
            }, 600);
        }
    });
</script>
