<table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">ID</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Usuario
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Artículo
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Cantidad
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Precio
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Cliente
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Fecha</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Total</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($ventas as $venta)
                                @if ($venta->articulo->nombre != 'Saldar pago')
                                    <tr>
                                        <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">
                                            {{ $venta->id }}</td>
                                        <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">
                                            {{ $venta->user->name }}</td>
                                        <td
                                            class="px-6 py-4 text-center whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $venta->articulo->nombre }}</td>
                                        <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">
                                            {{ $venta->cantidad }}</td>
                                        <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">
                                            ${{ number_format($venta->precio_venta, 2) }}</td>
                                        <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">
                                            {{ $venta->user->name }}</td>
                                        <td class="px-3 py-4 text-center whitespace-nowrap text-sm text-gray-500">
                                            {{ \Carbon\Carbon::parse($venta->created_at)->translatedFormat('l d/m/Y') }}
                                        </td>
                                        <td class=" py- text-center whitespace-nowrap text-sm text-gray-500">
                                            ${{ number_format($venta->total_venta, 2) }}</td>
                                        @if (Auth::user()->hasRole('admin'))
                                            <td class="px-1 py-4 text-center whitespace-nowrap text-sm text-gray-500">
                                                {{-- Botón Editar --}}
                                                <a href="{{ route('ventas.edit', $venta) }}"
                                                    class="text-indigo-600 hover:text-indigo-900">
                                                    Editar
                                                </a>
                                                {{-- Botón Eliminar --}}
                                                <form class="inline-block ml-4"
                                                    action="{{ route('ventas.destroy', $venta) }}" method="POST"
                                                    onsubmit="return confirm('¿Eliminar esta venta?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                                        Eliminar
                                                        </buttoIn>
                                                </form>
                                            </td>
                                        @endif
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-4 border text-center text-gray-600">
                                        No hay ventas registradas
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>