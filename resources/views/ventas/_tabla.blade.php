<table class="min-w-full divide-y divide-gray-200">
    <thead class="bg-gray-50">
        <tr>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">ID</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Usuario</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Artículo</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Cantidad</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Precio</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Cliente</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Fecha</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Total</th>
            @if (Auth::user()->hasRole('admin'))
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Acciones</th>
            @endif
        </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
        @forelse($ventas as $venta)
            @if ($venta->articulo && $venta->articulo->nombre != 'Pago saldado')
                <tr>
                    <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">
                        {{ $venta->id }}
                    </td>
                    <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">
                        {{ $venta->user ? $venta->user->name : 'N/A' }}
                    </td>
                    <td class="px-6 py-4 text-center whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ $venta->articulo ? $venta->articulo->nombre : 'N/A' }}
                    </td>
                    <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">
                        {{ $venta->cantidad }}
                    </td>
                    <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">
                        ${{ number_format($venta->precio_venta, 2) }}
                    </td>
                    <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">
                        {{ $venta->user ? $venta->user->name : 'N/A' }}
                    </td>
                    <td class="px-3 py-4 text-center whitespace-nowrap text-sm text-gray-500">
                        {{ \Carbon\Carbon::parse($venta->created_at)->translatedFormat('l d/m/Y') }}
                    </td>
                    <td class="py-4 text-center whitespace-nowrap text-sm text-gray-500">
                        ${{ number_format($venta->total_venta, 2) }}
                    </td>
                    @if (Auth::user()->hasRole('admin'))
                        <td class="px-1 py-4 text-center whitespace-nowrap text-sm text-gray-500">
                            <div class="flex items-center justify-center gap-3">

                                {{-- Botón Imprimir Nota --}}
                                <a href="{{ route('ventas.show', $venta) }}" target="_blank"
                                    title="Imprimir Nota de Venta"
                                    class="inline-flex items-center gap-1 text-emerald-600 hover:text-emerald-800 font-semibold cursor-pointer">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                    </svg>
                                </a>

                                {{-- Botón Editar Modal --}}
                                <button type="button"
                                    onclick='openEditVentaModal({{ json_encode(['id' => $venta->id, 'articulo_id' => $venta->articulo_id, 'precio_venta' => $venta->precio_venta, 'cantidad' => $venta->cantidad, 'user_id' => $venta->user_id, 'descripcion' => $venta->descripcion]) }})'
                                    class="inline-flex items-center justify-center text-indigo-600 hover:text-indigo-900 font-semibold cursor-pointer">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>

                                {{-- Botón Eliminar --}}
                                <form id="delete-venta-{{ $venta->id }}" class="contents"
                                    action="{{ route('ventas.destroy', $venta) }}" method="POST"
                                    onsubmit="return confirmDelete(this, 'esta venta');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="inline-flex items-center justify-center text-red-600 hover:text-red-900 font-semibold cursor-pointer">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>

                            </div>
                        </td>
                    @endif
                </tr>
            @endif
        @empty
            <tr>
                <td colspan="9" class="px-6 py-4 border text-center text-gray-600">
                    No hay ventas registradas
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
