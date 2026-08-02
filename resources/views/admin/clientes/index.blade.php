<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Gestión de clientes') }}
            </h2>
            <button type="button" onclick="openModal('create-cliente')"
                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-sm shadow-md shadow-indigo-500/25 hover:shadow-lg hover:shadow-indigo-500/35 transition-all duration-200 transform hover:-translate-y-0.5 focus:outline-none cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('Nuevo Cliente') }}
            </button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form method="GET" action="{{ route('admin.clientes.index') }}" class="mb-4 flex gap-2">
                <input id="q" type="text" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre…"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring focus:ring-indigo-200 px-4 py-2">
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
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

    <!-- Modal Nuevo Cliente -->
    <x-modal name="create-cliente">
        <div class="p-6">
            <div class="flex justify-between items-center pb-3 border-b mb-4">
                <h3 class="text-lg font-bold text-gray-900">Registrar Nuevo Cliente</h3>
                <button type="button" onclick="closeModal('create-cliente')" class="text-gray-400 hover:text-gray-600 font-bold text-xl">&times;</button>
            </div>
            <form method="POST" action="{{ route('admin.clientes.store') }}" enctype="multipart/form-data">
                @csrf
                @include('admin.clientes._form', ['cliente' => new \App\Models\User()])
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
</script>
