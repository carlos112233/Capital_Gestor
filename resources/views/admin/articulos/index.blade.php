{{-- resources/views/admin/articulos/index.blade.php --}}

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Gestión de Artículos') }}
            </h2>
            <a href="{{ route('admin.articulos.create') }}"
                class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900">
                {{ __('Nuevo Artículo') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative"
                    role="alert">
                    {{ session('success') }}
                </div>
            @endif
            <div class="mb-4 flex gap-2">
                <input type="text" id="search" placeholder="Buscar cliente..."
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring focus:ring-indigo-200 px-4 py-2">
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">ID</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Nombre
                                </th>
                                 <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Stock
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Precio
                                    Unitario</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($articulos as $articulo)
                                @if ($articulo->nombre != 'Saldar pago')
                                    <tr>
                                        <td
                                            class="px-6 py-4 text-center whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $articulo->id }}</td>
                                        <td
                                            class="px-6 py-4 text-center whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $articulo->nombre }}</td>

                                            <td
                                            class="px-6 py-4 text-center whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $articulo->stock }}</td>
                                        <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">$
                                            {{ number_format($articulo->precio, 2) }} MXN.</td>
                                        <td
                                            class="px-6 py-4 text-center whitespace-nowrap text-center text-sm font-medium">
                                            <a href="{{ route('admin.articulos.edit', $articulo) }}"
                                                class="text-indigo-600 hover:text-indigo-900">Editar</a>
                                            <form class="inline-block ml-4"
                                                action="{{ route('admin.articulos.destroy', $articulo) }}"
                                                method="POST"
                                                onsubmit="return confirm('¿Estás seguro de que quieres eliminar este artículo?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="text-red-600 hover:text-red-900">Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">No hay
                                        artículos registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4">
                    {{ $articulos->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>


<script>
    document.addEventListener('DOMContentLoaded', function () {
        const input = document.getElementById('search');
        const table = document.querySelector('table tbody');

        input.addEventListener('input', function () {
            const filter = this.value.toLowerCase();
            const rows = table.querySelectorAll('tr');

            rows.forEach(row => {
                const cell = row.querySelector('td:nth-child(2)'); // columna Nombre
                if (!cell) return;

                const text = cell.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    });
</script>

