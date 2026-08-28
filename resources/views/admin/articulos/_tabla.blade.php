  <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Nombre</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Stock</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Disponible</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Precio Unitario</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($articulos as $articulo)
                                @if ($articulo->nombre != 'Pago saldado')
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="px-6 py-4 text-center whitespace-nowrap text-sm font-medium text-slate-900">
                                            {{ $articulo->id }}</td>
                                        <td class="px-6 py-4 text-center whitespace-nowrap text-sm font-medium text-slate-900">
                                            {{ $articulo->nombre }}</td>
                                        <td class="px-6 py-4 text-center whitespace-nowrap text-sm font-medium text-slate-900">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $articulo->stock > 0 ? 'bg-slate-100 text-slate-800' : 'bg-amber-100 text-amber-800' }}">
                                                {{ $articulo->stock }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center whitespace-nowrap text-sm font-medium">
                                            <button type="button" onclick="toggleDisponibilidad({{ $articulo->id }}, this)"
                                                title="{{ $articulo->disponible ? 'Artículo Disponible - Clic para ocultar' : 'Artículo No Disponible - Clic para mostrar' }}"
                                                class="p-2 inline-flex items-center justify-center rounded-full cursor-pointer transition-all duration-200 border shadow-sm {{ $articulo->disponible ? 'bg-emerald-50 border-emerald-200 text-emerald-600 hover:bg-emerald-100 hover:scale-110' : 'bg-rose-50 border-rose-200 text-rose-600 hover:bg-rose-100 hover:scale-110' }}">
                                                @if($articulo->disponible)
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                @else
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                @endif
                                            </button>
                                        </td>
                                        <td class="px-6 py-4 text-center whitespace-nowrap text-sm font-semibold text-slate-700">
                                            ${{ number_format($articulo->precio, 2) }} MXN.
                                        </td>
                                        <td class="px-6 py-4 text-center whitespace-nowrap text-sm font-medium">
                                            <div class="flex items-center justify-center gap-2">
                                                {{-- Botón Editar Modal --}}
                                                <button type="button" onclick="openModal('edit-articulo-{{ $articulo->id }}')"
                                                    title="Editar Artículo"
                                                    class="p-2 text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition-colors cursor-pointer">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                    </svg>
                                                </button>

                                                {{-- Botón Eliminar --}}
                                                <form id="delete-articulo-{{ $articulo->id }}" class="inline-block"
                                                    action="{{ route('admin.articulos.destroy', $articulo) }}" method="POST"
                                                    onsubmit="return confirmDelete(this, 'este artículo');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" title="Eliminar Artículo"
                                                        class="p-2 text-rose-600 hover:text-rose-900 bg-rose-50 hover:bg-rose-100 rounded-lg transition-colors cursor-pointer">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>

                                            <!-- Modal Editar Artículo -->
                                            <x-modal name="edit-articulo-{{ $articulo->id }}">
                                                <div class="p-6 text-left">
                                                    <div class="flex justify-between items-center pb-3 border-b mb-4">
                                                        <h3 class="text-lg font-bold text-gray-900">Editar Artículo: {{ $articulo->nombre }}</h3>
                                                        <button type="button" onclick="closeModal('edit-articulo-{{ $articulo->id }}')" class="text-gray-400 hover:text-gray-600 font-bold text-xl">&times;</button>
                                                    </div>
                                                    <form method="POST" action="{{ route('admin.articulos.update', $articulo) }}" enctype="multipart/form-data">
                                                        @csrf
                                                        @method('PUT')
                                                        @include('admin.articulos._form', ['articulo' => $articulo])
                                                    </form>
                                                </div>
                                            </x-modal>
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No hay artículos registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

<script>
function toggleDisponibilidad(id, btn) {
    const originalHTML = btn.innerHTML;
    btn.innerHTML = `<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>`;
    btn.disabled = true;

    fetch(`/admin/articulos/${id}/toggle-disponible`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            if(data.disponible) {
                btn.className = 'p-2 inline-flex items-center justify-center rounded-full cursor-pointer transition-all duration-200 border shadow-sm bg-emerald-50 border-emerald-200 text-emerald-600 hover:bg-emerald-100 hover:scale-110';
                btn.title = 'Artículo Disponible - Clic para ocultar';
                btn.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>`;
            } else {
                btn.className = 'p-2 inline-flex items-center justify-center rounded-full cursor-pointer transition-all duration-200 border shadow-sm bg-rose-50 border-rose-200 text-rose-600 hover:bg-rose-100 hover:scale-110';
                btn.title = 'Artículo No Disponible - Clic para mostrar';
                btn.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>`;
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btn.innerHTML = originalHTML;
    })
    .finally(() => {
        btn.disabled = false;
    });
}
</script>