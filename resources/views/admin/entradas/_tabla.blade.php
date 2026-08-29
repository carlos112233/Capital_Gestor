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
                            <span>Recibo</span>
                        </a>

                        {{-- Botón Reenviar PDF por WhatsApp --}}
                        <form action="{{ route('admin.entradas.reenviar-whatsapp', $entrada) }}" method="POST" class="inline-flex m-0">
                            @csrf
                            <button type="submit" title="Reenviar Recibo PDF por WhatsApp"
                                class="inline-flex items-center gap-1 text-emerald-600 hover:text-emerald-800 font-semibold cursor-pointer border-0 bg-transparent p-0">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-1.157 4.228 4.228-1.157zm7.253-5.233c-.275-.138-1.625-.802-1.876-.893-.25-.092-.433-.138-.616.138-.184.276-.714.893-.875 1.077-.161.184-.322.207-.597.069-.275-.138-1.164-.429-2.217-1.368-.82-.731-1.374-1.634-1.535-1.91-.161-.276-.017-.425.12-.562.124-.124.275-.322.413-.483.138-.161.184-.276.275-.459.092-.184.046-.345-.023-.483-.069-.138-.616-1.487-.843-2.035-.221-.535-.446-.462-.616-.471-.158-.008-.344-.008-.529-.008-.184 0-.483.069-.735.345-.252.276-.963.941-.963 2.296 0 1.355.986 2.662 1.124 2.846.138.184 1.94 2.963 4.7 4.157.657.284 1.17.453 1.57.58.66.21 1.26.18 1.733.11.528-.078 1.625-.664 1.854-1.306.229-.642.229-1.192.161-1.306-.068-.114-.249-.183-.524-.321z"/>
                                </svg>
                                <span>Reenvío PDF</span>
                            </button>
                        </form>

                        {{-- Botón Editar Modal --}}
                        <button type="button" onclick='openEditModal({{ json_encode(["id" => $entrada->id, "articulo_id" => $entrada->articulo_id, "precio_venta" => $entrada->precio_venta, "cliente_id" => $entrada->cliente_id ?? $entrada->user_id, "user_id" => $entrada->user_id, "descripcion" => $entrada->descripcion, "articulo" => $entrada->articulo ? ["nombre" => $entrada->articulo->nombre] : null]) }})'
                            class="text-indigo-600 hover:text-indigo-900 font-semibold cursor-pointer">
                            Editar
                        </button>

                        {{-- Botón Eliminar con SweetAlert2 --}}
                        <form id="delete-entrada-{{ $entrada->id }}" class="inline-flex m-0"
                            action="{{ route('admin.entradas.destroy', $entrada) }}" method="POST"
                            onsubmit="return confirmDelete(this, 'esta entrada');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900 font-semibold cursor-pointer">
                                Eliminar
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