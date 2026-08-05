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
                    {{ $cliente->name }}
                    @if($cliente->trashed())
                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                            Inactivo
                        </span>
                    @endif
                </td>
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

                    @if($cliente->trashed())
                        {{-- Botón Activar --}}
                        <form id="activate-cliente-{{ $cliente->id }}" class="inline-block ml-4"
                            action="{{ route('admin.clientes.activar', $cliente->id) }}" method="POST"
                            onsubmit="return confirmDelete(this, 'este cliente (reactivarlo)');">
                            @csrf
                            <button type="submit" class="text-green-600 hover:text-green-900 font-semibold cursor-pointer">
                                Activar
                            </button>
                        </form>
                    @else
                        {{-- Botón Eliminar con SweetAlert2 --}}
                        <form id="delete-cliente-{{ $cliente->id }}" class="inline-block ml-4"
                            action="{{ route('admin.clientes.destroy', $cliente->id) }}" method="POST"
                            onsubmit="return confirmDelete(this, 'este cliente');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900 font-semibold cursor-pointer">
                                Dar de Baja
                            </button>
                        </form>
                    @endif

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
                <td colspan="7" class="px-4 py-8 border-b text-center text-gray-500 bg-gray-50/50">
                    <div class="flex flex-col items-center justify-center">
                        <svg class="w-10 h-10 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        @if(request()->filled('q'))
                            <span class="font-medium text-gray-700">No se encontraron clientes que coincidan con "{{ request()->q }}"</span>
                            <span class="text-sm mt-1">Intenta buscar con otro término.</span>
                        @else
                            <span class="font-medium text-gray-700">Utiliza el buscador para encontrar clientes</span>
                            <span class="text-sm mt-1">Escribe el nombre, correo o teléfono del cliente.</span>
                        @endif
                    </div>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
