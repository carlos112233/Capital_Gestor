<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Gestión de Ventas') }}
            </h2>
            <button id="tour-btn-nueva-venta" type="button" onclick="openModal('create-venta')"
                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-sm shadow-md shadow-indigo-500/25 hover:shadow-lg hover:shadow-indigo-500/35 transition-all duration-200 transform hover:-translate-y-0.5 focus:outline-none cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                {{ Auth::user()->hasRole('admin') ? __('Nueva venta') : __('Nueva compra') }}
            </button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form method="GET" action="{{ route('ventas.index') }}" class="mb-6 flex gap-2">
                <div class="relative w-full group" id="tour-buscador-ventas">
    <input type="text" name="q" id="q" value="{{ request('q') }}" class="block rounded-t-lg px-3 pb-2 pt-6 w-full text-sm text-slate-800 bg-slate-100 border-0 border-b-2 border-slate-300 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer pr-10 transition-colors focus:bg-slate-200/50" placeholder=" " autocomplete="off" />
    <label for="q" class="absolute text-sm text-slate-500 duration-300 transform -translate-y-4 scale-75 top-4 z-10 origin-[0] start-3 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-4 cursor-text">
        Buscar por cliente, artículo o descripción...
    </label>
    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-400 group-focus-within:text-indigo-600 transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
    </div>
</div>
            </form>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">

                <div id="contenedor-tabla" class="mt-4 overflow-x-auto">
                    @include('ventas._tabla', ['ventas' => $ventas])
                </div>

                <div class="p-4">
                    {{ $ventas->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nueva Venta -->
    <x-modal name="create-venta" :show="$errors->any() && !old('_method')">
        <div class="p-6">
            <div class="flex justify-between items-center pb-3 border-b mb-4">
                <h3 class="text-lg font-bold text-gray-900">Registrar Nueva Venta</h3>
                <button type="button" onclick="closeModal('create-venta')" class="text-gray-400 hover:text-gray-600 font-bold text-xl">&times;</button>
            </div>
            <form id="form-create-venta" method="POST" action="{{ route('ventas.store') }}">
                @csrf
                @include('ventas._form', ['venta' => new \App\Models\Venta()])
            </form>
        </div>
    </x-modal>

    <!-- Modal Editar Venta Unificado -->
    <x-modal name="edit-venta" :show="$errors->any() && old('_method') === 'PUT'">
        <div class="p-6 text-left">
            <div class="flex justify-between items-center pb-3 border-b mb-4">
                <h3 id="edit-modal-title-venta" class="text-lg font-bold text-gray-900">Editar Venta</h3>
                <button type="button" onclick="closeModal('edit-venta')" class="text-gray-400 hover:text-gray-600 font-bold text-xl">&times;</button>
            </div>
            <form id="form-edit-venta" method="POST" action="">
                @csrf
                @method('PUT')
                @include('ventas._form', ['venta' => new \App\Models\Venta()])
            </form>
        </div>
    </x-modal>

</x-app-layout>

<script>
    function openEditVentaModal(venta) {
        const form = document.getElementById('form-edit-venta');
        const title = document.getElementById('edit-modal-title-venta');
        if (form && venta) {
            form.action = "{{ url('ventas') }}/" + venta.id;
            if (title) title.textContent = "Editar Venta #" + venta.id;

            const articuloSelect = form.querySelector('[name="articulo_id"]');
            const precioInput = form.querySelector('[name="precio_venta"]');
            const cantidadInput = form.querySelector('[name="cantidad"]');
            const clienteSelect = form.querySelector('[name="cliente_id"]');
            const descripcionInput = form.querySelector('[name="descripcion"]');

            if (articuloSelect) {
                if (articuloSelect.tomselect) articuloSelect.tomselect.setValue(venta.articulo_id || '');
                else articuloSelect.value = venta.articulo_id || '';
            }
            if (precioInput) precioInput.value = parseFloat(venta.precio_venta || 0).toFixed(2);
            if (cantidadInput) cantidadInput.value = venta.cantidad || 1;
            if (clienteSelect) {
                if (clienteSelect.tomselect) clienteSelect.tomselect.setValue(venta.user_id || '');
                else clienteSelect.value = venta.user_id || '';
            }
            if (descripcionInput) descripcionInput.value = venta.descripcion || '';
        }
        openModal('edit-venta');
    }

    const buscador = document.getElementById('q');
    let timeout = null;

    if (buscador) {
        buscador.addEventListener('keyup', function() {
            clearTimeout(timeout);
            const query = this.value;

            timeout = setTimeout(() => {
                fetch("{{ route('ventas.index') }}?q=" + encodeURIComponent(query), {
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
    }
</script>

@php
    $hasSeenTutorial = Auth::check() && Auth::user()->tutorials()->where('tutorial_name', 'ventas')->exists();
    $isAdmin = Auth::check() && Auth::user()->hasRole('admin');
@endphp

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const forceTutorial = new URLSearchParams(window.location.search).get('tutorial') === 'true';
        const hasSeenTutorial = @json($hasSeenTutorial);
        const isAdmin = @json($isAdmin);

        if (forceTutorial || !hasSeenTutorial) {
            const driverObj = window.driver.js.driver({
                showProgress: true,
                nextBtnText: 'Siguiente ➔',
                prevBtnText: '⬅ Anterior',
                doneBtnText: '¡Entendido!',
                progressText: 'Paso @{{current}} de @{{total}}',
                steps: [
                    {
                        element: '#tour-btn-nueva-venta',
                        popover: {
                            title: isAdmin ? 'Registrar Venta' : 'Realizar Compra',
                            description: isAdmin ? 'Haz clic aquí para registrar una venta manualmente.' : 'Haz clic aquí para registrar una nueva compra manualmente si no usaste el catálogo.',
                            side: "bottom",
                            align: 'end'
                        }
                    },
                    {
                        element: '#tour-buscador-ventas',
                        popover: {
                            title: 'Búsqueda Rápida',
                            description: 'Encuentra fácilmente cualquier registro por descripción o artículo.',
                            side: "bottom",
                            align: 'start'
                        }
                    },
                    {
                        element: '#contenedor-tabla',
                        popover: {
                            title: isAdmin ? 'Historial de Ventas' : 'Historial de Compras',
                            description: 'Aquí verás todo el historial detallado, incluyendo fechas y totales.',
                            side: "top",
                            align: 'start'
                        }
                    }
                ],
                onDestroyStarted: () => {
                    if (!driverObj.hasNextStep() || confirm("¿Seguro que quieres saltar el tutorial?")) {
                        driverObj.destroy();
                        fetch('{{ route("tutorial.marcar-visto") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ tutorial_name: 'ventas' })
                        });
                    }
                }
            });

            if (forceTutorial) {
                const url = new URL(window.location);
                url.searchParams.delete('tutorial');
                window.history.replaceState({}, '', url);
            }

            driverObj.drive();
        }
    });
</script>
