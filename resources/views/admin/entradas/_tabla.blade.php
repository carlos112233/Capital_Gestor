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
                                         {{ ucfirst($entrada->fecha_generado)->setTimezone('America/Mexico_City')->translatedFormat('l d/m/Y')}}
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