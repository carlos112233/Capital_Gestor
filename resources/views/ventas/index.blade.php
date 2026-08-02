<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Gestión de clientes') }}
            </h2>
            <button type="button" onclick="openModal('create-venta')"
                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-sm shadow-md shadow-indigo-500/25 hover:shadow-lg hover:shadow-indigo-500/35 transition-all duration-200 transform hover:-translate-y-0.5 focus:outline-none cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('Nueva venta') }}
            </button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form method="GET" action="{{ route('ventas.index') }}" class="mb-6 flex gap-2">
                <input id="q" type="text" name="q" value="{{ request('q') }}"
                    placeholder="Buscar por nombre…" autocomplete="off"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring focus:ring-indigo-200">
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
    <x-modal name="create-venta" :show="$errors->any()">
        <div class="p-6">
            <div class="flex justify-between items-center pb-3 border-b mb-4">
                <h3 class="text-lg font-bold text-gray-900">Registrar Nueva Venta</h3>
                <button type="button" onclick="closeModal('create-venta')" class="text-gray-400 hover:text-gray-600 font-bold text-xl">&times;</button>
            </div>
            <form method="POST" action="{{ route('ventas.store') }}">
                @csrf
                @include('ventas._form', ['venta' => new \App\Models\Venta()])
            </form>
        </div>
    </x-modal>

</x-app-layout>
<script>
    const buscador = document.getElementById('q');
    let timeout = null;

    buscador.addEventListener('keyup', function() {
        clearTimeout(timeout); // Limpiar búsqueda anterior
        const query = this.value;

        // Esperar 300ms después de dejar de escribir
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
</script>
