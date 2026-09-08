<table class="min-w-full divide-y divide-gray-200">
    <thead class="bg-gray-50">
        <tr>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">ID</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Usuario</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Artículo</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Cliente</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Precio</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Descripción</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Fecha</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Acciones</th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
        @forelse($entradas as $entrada)
            <tr>
                <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">
                    {{ $entrada->id }}</td>
                <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">
                    {{ $entrada->user->name ?? 'Usuario no disponible' }}</td>
                <td class="px-6 py-4 text-center whitespace-nowrap text-sm font-medium text-gray-900">
                    {{ $entrada->articulo->nombre ?? 'Artículo no disponible' }}</td>
                <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">
                    {{ $entrada->cliente->name ?? $entrada->user->name ?? 'N/A' }}</td>
                <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">
                    ${{ number_format($entrada->precio_venta ?? 0, 2) }}</td>
                <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">
                    {{ $entrada->descripcion ?? '-' }}</td>
                <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">
                    {{ $entrada->fecha_generado ? \Carbon\Carbon::parse($entrada->fecha_generado)->translatedFormat('l d/m/Y') : ($entrada->created_at ? $entrada->created_at->translatedFormat('l d/m/Y') : 'N/A') }}
                </td>
                <td class="px-6 py-4 text-center whitespace-nowrap text-sm text-gray-500">
                    <div class="flex items-center justify-center gap-3">
                        {{-- Botón Imprimir Recibo --}}
                        <a href="{{ route('admin.entradas.show', $entrada) }}" target="_blank" title="Ver / Imprimir Recibo de Pago"
                            class="inline-flex items-center gap-1 text-slate-500 hover:text-slate-800 font-semibold cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
                        </a>

                        {{-- Botón Reenviar PDF por WhatsApp --}}
                        <form action="{{ route('admin.entradas.reenviar-whatsapp', $entrada) }}" method="POST" class="inline-flex m-0">
                            @csrf
                            <button type="submit" title="Reenviar Recibo PDF por WhatsApp"
                                class="inline-flex items-center gap-1 text-emerald-600 hover:text-emerald-800 font-semibold cursor-pointer border-0 bg-transparent p-0">
                                <svg class="w-4 h-4 text-emerald-600 fill-current" viewBox="0 0 24 24">
                                    <path
                                        d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.205 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z" />
                                </svg>
                                <span>PDF</span>
                            </button>
                        </form>

                        {{-- Botón Editar Modal --}}
                        <button type="button" onclick='openEditModal({{ json_encode(["id" => $entrada->id, "articulo_id" => $entrada->articulo_id, "precio_venta" => $entrada->precio_venta, "cliente_id" => $entrada->cliente_id ?? $entrada->user_id, "user_id" => $entrada->user_id, "descripcion" => $entrada->descripcion, "articulo" => $entrada->articulo ? ["nombre" => $entrada->articulo->nombre] : null]) }})'
                            class="inline-flex items-center justify-center text-indigo-600 hover:text-indigo-900 font-semibold cursor-pointer" title="Editar">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                        </button>

                        {{-- Botón Eliminar con SweetAlert2 --}}
                        <form id="delete-entrada-{{ $entrada->id }}" class="contents"
                            action="{{ route('admin.entradas.destroy', $entrada) }}" method="POST"
                            onsubmit="return confirmDelete(this, 'esta entrada');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center justify-center text-red-600 hover:text-red-900 font-semibold cursor-pointer" title="Eliminar">
                                  <svg xmlns="http://w3.org" fill="none" viewBox="0 0 20 20" stroke-width="2"
                                          stroke="currentColor" class="w-6 h-6">
                                          <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M6 18 18 6M6 6l12 12" />
                                      </svg>
                            </button>
                        </form>
                    </div>
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