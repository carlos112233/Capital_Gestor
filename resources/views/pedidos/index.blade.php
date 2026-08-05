{{-- resources/views/pedidos/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Pedidos') }}
            </h2>
            @if($articulos->isEmpty())
                <button type="button" onclick="Swal.fire({title: 'Atención', text: 'No hay artículos disponibles para realizar un pedido en este momento.', icon: 'info', confirmButtonColor: '#4f46e5'})"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-sm shadow-md shadow-indigo-500/25 hover:shadow-lg hover:shadow-indigo-500/35 transition-all duration-200 transform hover:-translate-y-0.5 focus:outline-none cursor-pointer opacity-75">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('Nuevo Pedido') }}
                </button>
            @else
                <button type="button" onclick="openModal('create-pedido')"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-sm shadow-md shadow-indigo-500/25 hover:shadow-lg hover:shadow-indigo-500/35 transition-all duration-200 transform hover:-translate-y-0.5 focus:outline-none cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('Nuevo Pedido') }}
                </button>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form method="GET" action="{{ route('pedidos.index') }}" class="mb-4 flex gap-2">
                <div class="relative z-0 w-full group">
    <input type="text" name="q" id="search" value="{{ request('q') }}" class="block py-2.5 px-0 w-full text-sm text-slate-800 bg-transparent border-0 border-b-2 border-slate-300 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" placeholder=" " autocomplete="off" />
    <label for="search" class="peer-focus:font-medium absolute text-sm text-slate-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6 flex items-center gap-2 cursor-text">
        <svg class="w-4 h-4 text-slate-400 group-focus-within:text-indigo-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        Buscar pedidos por artículo, descripción o usuario...
    </label>
</div>
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">ID</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Artículo</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Descripción</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Usuario</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Costo</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Fecha</th>
                                @if (Auth::user()->hasRole('admin'))
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Acciones</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($pedidos as $pedido)
                                <tr>
                                    <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">{{ $pedido->id }}</td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap text-sm font-medium text-gray-900">{{ $pedido->articulo->nombre ?? '' }}</td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">{{ $pedido->descripcion }}</td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">{{ $pedido->user->name ?? '' }}</td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">${{ number_format($pedido->costo, 2) }}</td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">{{ \Carbon\Carbon::parse($pedido->created_at)->translatedFormat('l d/m/Y') }}</td>
                                    @if (Auth::user()->hasRole('admin'))
                                        <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">
                                            {{-- Botón Editar Modal --}}
                                            <button type="button" onclick="openModal('edit-pedido-{{ $pedido->id }}')"
                                                class="text-indigo-600 hover:text-indigo-900 font-semibold cursor-pointer">
                                                Editar
                                            </button>

                                            {{-- Botón Eliminar con SweetAlert2 --}}
                                            <form id="delete-pedido-{{ $pedido->id }}" class="inline-block ml-4"
                                                action="{{ route('pedidos.destroy', $pedido) }}" method="POST"
                                                onsubmit="return confirmDelete(this, 'este pedido');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900 font-semibold cursor-pointer">
                                                    Eliminar
                                                </button>
                                            </form>

                                            <!-- Modal Editar Pedido -->
                                            <x-modal name="edit-pedido-{{ $pedido->id }}">
                                                <div class="p-6 text-left">
                                                    <div class="flex justify-between items-center pb-3 border-b mb-4">
                                                        <h3 class="text-lg font-bold text-gray-900">Editar Pedido #{{ $pedido->id }}</h3>
                                                        <button type="button" onclick="closeModal('edit-pedido-{{ $pedido->id }}')" class="text-gray-400 hover:text-gray-600 font-bold text-xl">&times;</button>
                                                    </div>
                                                    <form method="POST" action="{{ route('pedidos.update', $pedido) }}">
                                                        @csrf
                                                        @method('PUT')
                                                        @include('pedidos._form', ['pedido' => $pedido])
                                                    </form>
                                                </div>
                                            </x-modal>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-4 border text-center text-gray-600">
                                        No hay pedidos registrados
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4">
                    {{ $pedidos->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nuevo Pedido -->
    <x-modal name="create-pedido">
        <div class="p-6">
            <div class="flex justify-between items-center pb-3 border-b mb-4">
                <h3 class="text-lg font-bold text-gray-900">Crear Nuevo Pedido</h3>
                <button type="button" onclick="closeModal('create-pedido')" class="text-gray-400 hover:text-gray-600 font-bold text-xl">&times;</button>
            </div>
            <form method="POST" action="{{ route('pedidos.store') }}">
                @csrf
                @include('pedidos._form', ['pedido' => new \App\Models\Pedido()])
            </form>
        </div>
    </x-modal>
</x-app-layout>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const input = document.getElementById('search');
        const table = document.querySelector('table tbody');

        if (input && table) {
            input.addEventListener('input', function() {
                const filter = this.value.toLowerCase();
                const rows = table.querySelectorAll('tr');

                rows.forEach(row => {
                    // Columnas: Artículo (2), Descripción (3), Usuario (4)
                    const articulo = row.querySelector('td:nth-child(2)')?.textContent
                    .toLowerCase() || '';
                    const descripcion = row.querySelector('td:nth-child(3)')?.textContent
                        .toLowerCase() || '';
                    const usuario = row.querySelector('td:nth-child(4)')?.textContent
                    .toLowerCase() || '';

                    // Mostrar fila si alguna columna coincide
                    if (articulo.includes(filter) || descripcion.includes(filter) || usuario
                        .includes(filter)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
    });
</script>
