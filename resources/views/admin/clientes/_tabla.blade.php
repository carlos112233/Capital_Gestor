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
                    <div class="flex items-center justify-center gap-2">
                        <img src="{{ route('user.image', $cliente->id) }}?v={{ $cliente->updated_at ? $cliente->updated_at->timestamp : '' }}" class="w-7 h-7 rounded-full object-cover border border-slate-200 shadow-sm" alt="Foto">
                        <span class="font-medium text-slate-800">{{ $cliente->name }}</span>
                    </div>
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
                    <div class="flex items-center justify-center gap-3">
                        {{-- Botón Enviar WhatsApp --}}
                        @if(!empty($cliente->telefono))
                            <button type="button" onclick="openModal('wa-cliente-{{ $cliente->id }}')"
                                title="Enviar accesos por WhatsApp"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 hover:text-emerald-900 font-bold text-xs transition-all border border-emerald-200 shadow-sm cursor-pointer">
                                <svg class="w-4 h-4 text-emerald-600 fill-current" viewBox="0 0 24 24">
                                    <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.205 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                                </svg>
                                Accesos
                            </button>
                        @endif

                        {{-- Botón Editar Modal --}}
                        <button type="button" onclick="openModal('edit-cliente-{{ $cliente->id }}')"
                            class="text-indigo-600 hover:text-indigo-900 font-semibold cursor-pointer">
                            Editar
                        </button>

                        @if($cliente->trashed())
                            {{-- Botón Activar --}}
                            <form id="activate-cliente-{{ $cliente->id }}" class="inline-block"
                                action="{{ route('admin.clientes.activar', $cliente->id) }}" method="POST"
                                onsubmit="return confirmDelete(this, 'este cliente (reactivarlo)');">
                                @csrf
                                <button type="submit" class="text-green-600 hover:text-green-900 font-semibold cursor-pointer">
                                    Activar
                                </button>
                            </form>
                        @else
                            {{-- Botón Eliminar con SweetAlert2 --}}
                            <form id="delete-cliente-{{ $cliente->id }}" class="inline-block"
                                action="{{ route('admin.clientes.destroy', $cliente->id) }}" method="POST"
                                onsubmit="return confirmDelete(this, 'este cliente');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900 font-semibold cursor-pointer">
                                    Dar de Baja
                                </button>
                            </form>
                        @endif
                    </div>

                    @if(!empty($cliente->telefono))
                        <!-- Modal WhatsApp Accesos -->
                        <x-modal name="wa-cliente-{{ $cliente->id }}">
                            <div class="p-6 text-left">
                                <div class="flex justify-between items-center pb-3 border-b mb-4">
                                    <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                                        <svg class="w-6 h-6 text-emerald-600 fill-current" viewBox="0 0 24 24">
                                            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.205 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                                        </svg>
                                        <span>Enviar Accesos a {{ $cliente->name }}</span>
                                    </h3>
                                    <button type="button" onclick="closeModal('wa-cliente-{{ $cliente->id }}')" class="text-gray-400 hover:text-gray-600 font-bold text-xl">&times;</button>
                                </div>

                                <div class="bg-gray-50 rounded-xl p-3 border mb-4 text-xs text-gray-700 space-y-1">
                                    <p><strong>Teléfono:</strong> {{ $cliente->telefono }}</p>
                                    <p><strong>Email / Usuario:</strong> {{ $cliente->email }}</p>
                                </div>

                                <form method="POST" action="{{ route('admin.clientes.enviar-whatsapp', $cliente->id) }}">
                                    @csrf
                                    <div class="mb-4">
                                        <label class="block text-gray-700 font-semibold text-sm mb-1">Nueva Contraseña (Opcional):</label>
                                        <input type="text" name="password" placeholder="Dejar en blanco para mantener la contraseña actual" class="w-full border-gray-300 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500">
                                        <p class="text-xs text-gray-500 mt-1">Si ingresas un texto aquí, la contraseña del cliente se actualizará a este valor y se enviará por WhatsApp.</p>
                                    </div>

                                    <div class="flex justify-end gap-3 mt-6">
                                        <button type="button" onclick="closeModal('wa-cliente-{{ $cliente->id }}')" class="px-4 py-2 rounded-xl border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 text-sm font-semibold cursor-pointer">
                                            Cancelar
                                        </button>
                                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-sm shadow-md shadow-emerald-500/20 hover:shadow-lg transition-all flex items-center gap-2 cursor-pointer">
                                            <span>🚀 Enviar Accesos por WhatsApp</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </x-modal>
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
