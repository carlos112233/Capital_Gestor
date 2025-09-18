<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Gestión de clientes') }}
            </h2>
            <a href="{{ route('ventas.create') }}"
                class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900">
                {{ __('Nueva venta') }}
            </a>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative"
                    role="alert">
                    {{ session('success') }}
                </div>
            @endif
            <form method="GET" action="{{ route('ventas.index') }}" class="mb-6 flex gap-2">
                <input id="q" type="text" name="q" value="{{ request('q') }}"
                    placeholder="Buscar por nombre…" autocomplete="off"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring focus:ring-indigo-200">
            </form>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">

                <div id="contenedor-tabla" class="mt-4">
                    @include('ventas._tabla', ['ventas' => $ventas])
                </div>

                <div class="p-4">
                    {{ $ventas->links() }}
                </div>
            </div>
        </div>
    </div>





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
