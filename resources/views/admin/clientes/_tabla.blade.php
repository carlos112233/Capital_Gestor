<table class="min-w-full divide-y divide-gray-200">
    <thead class="bg-gray-50">
        <tr>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">ID</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Usuario</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Teléfono</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Email</th>
            <th class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Acciones</th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
        @forelse($clientes as $cliente)
            <tr>
                <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">
                    {{ $cliente->id }}</td>
                <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">
                    {{ $cliente->name }}</td>
                    <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">
                    {{ $cliente->telefono }}</td>
                <td class="px-6 py-4 text-center whitespace-nowrap text-sm font-medium text-gray-900">
                    {{ $cliente->email }}</td>
                {{-- <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">{{ $cliente->telefono }}</td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">{{ $cliente->direccion }}</td> --}}
                <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">
                    {{-- Botón Editar Modal --}}
                    <button type="button" onclick="openModal('edit-cliente-{{ $cliente->id }}')"
                        class="text-indigo-600 hover:text-indigo-900 font-semibold cursor-pointer">
                        Editar
                    </button>

                    {{-- Botón Eliminar con SweetAlert2 --}}
                    <form id="delete-cliente-{{ $cliente->id }}" class="inline-block ml-4"
                        action="{{ route('admin.clientes.destroy', $cliente) }}" method="POST"
                        onsubmit="return confirmDelete(this, 'este cliente');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-900 font-semibold cursor-pointer">
                            Eliminar
                        </button>
                    </form>

                    <!-- Modal Editar Cliente -->
                    <x-modal name="edit-cliente-{{ $cliente->id }}">
                        <div class="p-6 text-left">
                            <div class="flex justify-between items-center pb-3 border-b mb-4">
                                <h3 class="text-lg font-bold text-gray-900">Editar Cliente: {{ $cliente->name }}</h3>
                                <button type="button" onclick="closeModal('edit-cliente-{{ $cliente->id }}')" class="text-gray-400 hover:text-gray-600 font-bold text-xl">&times;</button>
                            </div>
                            <form method="POST" action="{{ route('admin.clientes.update', $cliente) }}" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                @include('admin.clientes._form', ['cliente' => $cliente])
                            </form>
                        </div>
                    </x-modal>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-4 py-2 border text-center text-gray-500">
                    No hay clientes registradas
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
