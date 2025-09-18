<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Mis Entradas de Capital') }}
            </h2>
            <a href="{{ route('admin.entradas.create') }}"
                class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                {{ __('Nueva entrada') }}
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
                <input type="text" id="search"
                    placeholder="Buscar entradas por usuario, artículo, cliente o descripción..."
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring focus:ring-indigo-200 px-4 py-2">
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">ID</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Usuario
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Artículo
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Cliente
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Precio
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">
                                    Descripción</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Fecha</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($entradas as $entrada)
                                <tr>
                                    <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">
                                        {{ $entrada->id }}</td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">
                                        {{ $entrada->user->name }}</td>
                                    <td
                                        class="px-6 py-4 text-center whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $entrada->articulo->nombre }}</td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">
                                        {{ $entrada->user->name }}</td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">
                                        ${{ number_format($entrada->precio_venta, 2) }}</td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">
                                        {{ $entrada->descripcion }}</td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">
                                        {{ \Carbon\Carbon::parse($entrada->fecha_generado)->translatedFormat('l d/m/Y') }}
                                    </td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">
                                        {{-- Botón Editar --}}
                                        <a href="{{ route('admin.entradas.edit', $entrada) }}"
                                            class="text-indigo-600 hover:text-indigo-900">
                                            Editar
                                        </a>
                                        {{-- Botón Eliminar --}}
                                        <form class="inline-block ml-4"
                                            action="{{ route('admin.entradas.destroy', $entrada) }}" method="POST"
                                            onsubmit="return confirm('¿Eliminar esta venta?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">
                                                Eliminar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-4 border text-center text-gray-600">
                                        No hay entradas registradas
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4">
                    {{ $entradas->links() }}
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
                // Columnas: Usuario (2), Email (3)
                const usuario = row.querySelector('td:nth-child(2)')?.textContent.toLowerCase() || '';
                const email = row.querySelector('td:nth-child(3)')?.textContent.toLowerCase() || '';

                // Mostrar fila si alguna columna coincide
                if (usuario.includes(filter) || email.includes(filter)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
</script>
