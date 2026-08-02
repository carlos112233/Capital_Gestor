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
                                        {{-- Botón Editar Modal --}}
                                        <button type="button" onclick="openModal('edit-entrada-{{ $entrada->id }}')"
                                            class="text-indigo-600 hover:text-indigo-900 font-semibold cursor-pointer">
                                            Editar
                                        </button>

                                        {{-- Botón Eliminar con SweetAlert2 --}}
                                        <form id="delete-entrada-{{ $entrada->id }}" class="inline-block ml-4"
                                            action="{{ route('admin.entradas.destroy', $entrada) }}" method="POST"
                                            onsubmit="return confirmDelete(this, 'esta entrada');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900 font-semibold cursor-pointer">
                                                Eliminar
                                            </button>
                                        </form>

                                        <!-- Modal Editar Entrada -->
                                        <x-modal name="edit-entrada-{{ $entrada->id }}">
                                            <div class="p-6 text-left">
                                                <div class="flex justify-between items-center pb-3 border-b mb-4">
                                                    <h3 class="text-lg font-bold text-gray-900">Editar Entrada #{{ $entrada->id }}</h3>
                                                    <button type="button" onclick="closeModal('edit-entrada-{{ $entrada->id }}')" class="text-gray-400 hover:text-gray-600 font-bold text-xl">&times;</button>
                                                </div>
                                                <form method="POST" action="{{ route('admin.entradas.update', $entrada) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    @include('admin.entradas._form', ['entrada' => $entrada])
                                                </form>
                                            </div>
                                        </x-modal>
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