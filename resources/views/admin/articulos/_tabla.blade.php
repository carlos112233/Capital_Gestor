  <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">ID</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Nombre
                                </th>
                                 <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Stock
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Disponible
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Precio
                                    Unitario</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($articulos as $articulo)
                                @if ($articulo->nombre != 'Pago saldado')
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
                                        <td class="px-6 py-4 text-center whitespace-nowrap text-sm font-medium">
                                            <button type="button" onclick="toggleDisponibilidad({{ $articulo->id }}, this)"
                                                class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full cursor-pointer transition-colors duration-200 {{ $articulo->disponible ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-red-100 text-red-800 hover:bg-red-200' }}">
                                                {{ $articulo->disponible ? 'Sí (Ocultar)' : 'No (Mostrar)' }}
                                            </button>
                                        </td>
                                        <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">$
                                            {{ number_format($articulo->precio, 2) }} MXN.</td>
                                        <td class="px-6 py-4 text-center whitespace-nowrap text-sm font-medium">
                                            {{-- Botón Editar Modal --}}
                                            <button type="button" onclick="openModal('edit-articulo-{{ $articulo->id }}')"
                                                class="text-indigo-600 hover:text-indigo-900 font-semibold cursor-pointer">
                                                Editar
                                            </button>

                                            {{-- Botón Eliminar con SweetAlert2 --}}
                                            <form id="delete-articulo-{{ $articulo->id }}" class="inline-block ml-4"
                                                action="{{ route('admin.articulos.destroy', $articulo) }}" method="POST"
                                                onsubmit="return confirmDelete(this, 'este artículo');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900 font-semibold cursor-pointer">
                                                    Eliminar
                                                </button>
                                            </form>

                                            <!-- Modal Editar Artículo -->
                                            <x-modal name="edit-articulo-{{ $articulo->id }}">
                                                <div class="p-6 text-left">
                                                    <div class="flex justify-between items-center pb-3 border-b mb-4">
                                                        <h3 class="text-lg font-bold text-gray-900">Editar Artículo: {{ $articulo->nombre }}</h3>
                                                        <button type="button" onclick="closeModal('edit-articulo-{{ $articulo->id }}')" class="text-gray-400 hover:text-gray-600 font-bold text-xl">&times;</button>
                                                    </div>
                                                    <form method="POST" action="{{ route('admin.articulos.update', $articulo) }}" enctype="multipart/form-data">
                                                        @csrf
                                                        @method('PUT')
                                                        @include('admin.articulos._form', ['articulo' => $articulo])
                                                    </form>
                                                </div>
                                            </x-modal>
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

<script>
function toggleDisponibilidad(id, btn) {
    const originalText = btn.innerText;
    btn.innerText = '...';
    btn.disabled = true;

    fetch(`/admin/articulos/${id}/toggle-disponible`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            if(data.disponible) {
                btn.className = 'px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full cursor-pointer transition-colors duration-200 bg-green-100 text-green-800 hover:bg-green-200';
                btn.innerText = 'Sí (Ocultar)';
            } else {
                btn.className = 'px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full cursor-pointer transition-colors duration-200 bg-red-100 text-red-800 hover:bg-red-200';
                btn.innerText = 'No (Mostrar)';
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btn.innerText = originalText;
    })
    .finally(() => {
        btn.disabled = false;
    });
}
</script>