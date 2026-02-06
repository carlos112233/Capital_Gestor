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