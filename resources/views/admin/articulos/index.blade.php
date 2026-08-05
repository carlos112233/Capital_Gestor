{{-- resources/views/admin/articulos/index.blade.php --}}

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Gestión de Artículos') }}
            </h2>
            <button type="button" onclick="openModal('create-articulo')"
                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-sm shadow-md shadow-indigo-500/25 hover:shadow-lg hover:shadow-indigo-500/35 transition-all duration-200 transform hover:-translate-y-0.5 focus:outline-none cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('Nuevo Artículo') }}
            </button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex gap-2">
                <div class="relative z-0 w-full group">
    <input type="text" name="q" id="q" value="{{ request('q') }}" class="block py-2.5 px-0 w-full text-sm text-slate-800 bg-transparent border-0 border-b-2 border-slate-300 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" placeholder=" " autocomplete="off" />
    <label for="q" class="peer-focus:font-medium absolute text-sm text-slate-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6 flex items-center gap-2 cursor-text">
        <svg class="w-4 h-4 text-slate-400 group-focus-within:text-indigo-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        Buscar artículos por nombre o descripción...
    </label>
</div>
                <form id="form-bulk-enable" action="{{ route('admin.articulos.bulk-disponible') }}" method="POST" class="whitespace-nowrap flex-shrink-0">
                    @csrf
                    <input type="hidden" name="status" value="1">
                    <button type="button" onclick="confirmBulkAction('form-bulk-enable', '¿Seguro que deseas marcar TODOS los artículos con stock como disponibles?')" class="h-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-emerald-300 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-sm shadow-sm transition-all duration-200">
                        Activar Todos
                    </button>
                </form>
                <form id="form-bulk-disable" action="{{ route('admin.articulos.bulk-disponible') }}" method="POST" class="whitespace-nowrap flex-shrink-0">
                    @csrf
                    <input type="hidden" name="status" value="0">
                    <button type="button" onclick="confirmBulkAction('form-bulk-disable', '¿Seguro que deseas marcar TODOS los artículos como NO disponibles?')" class="h-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-rose-300 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-sm shadow-sm transition-all duration-200">
                        Desactivar Todos
                    </button>
                </form>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <div id="contenedor-tabla" class="mt-4 overflow-x-auto">
                        @include('admin.articulos._tabla', ['articulos' => $articulos])
                    </div>
                </div>
                <div class="p-4">
                    {{ $articulos->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nuevo Artículo -->
    <x-modal name="create-articulo">
        <div class="p-6">
            <div class="flex justify-between items-center pb-3 border-b mb-4">
                <h3 class="text-lg font-bold text-gray-900">Crear Nuevo Artículo</h3>
                <button type="button" onclick="closeModal('create-articulo')" class="text-gray-400 hover:text-gray-600 font-bold text-xl">&times;</button>
            </div>
            <form method="POST" action="{{ route('admin.articulos.store') }}" enctype="multipart/form-data">
                @csrf
                @include('admin.articulos._form', ['articulo' => new \App\Models\Articulo()])
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
            fetch("{{ route('admin.articulos.index') }}?q=" + encodeURIComponent(query), {
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

    function confirmBulkAction(formId, message) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#4f46e5',
            cancelButtonColor: '#ef4444',
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById(formId).submit();
            }
        });
    }
</script>
