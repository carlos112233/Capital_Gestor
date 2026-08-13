{{-- resources/views/admin/articulos/index.blade.php --}}

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Gestión de Artículos') }}
            </h2>
            <div class="flex gap-2">
                <button type="button" onclick="openModal('ventas-multiples-modal')"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-sm shadow-md shadow-emerald-500/25 hover:shadow-lg hover:shadow-emerald-500/35 transition-all duration-200 transform hover:-translate-y-0.5 focus:outline-none cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    {{ __('Ventas Múltiples') }}
                </button>
                <button type="button" onclick="openModal('create-articulo')"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-sm shadow-md shadow-indigo-500/25 hover:shadow-lg hover:shadow-indigo-500/35 transition-all duration-200 transform hover:-translate-y-0.5 focus:outline-none cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('Nuevo Artículo') }}
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex gap-2">
                <div class="relative w-full group">
    <input type="text" name="q" id="q" value="{{ request('q') }}" class="block rounded-t-lg px-3 pb-2 pt-6 w-full text-sm text-slate-800 bg-slate-100 border-0 border-b-2 border-slate-300 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer pr-10 transition-colors focus:bg-slate-200/50" placeholder=" " autocomplete="off" />
    <label for="q" class="absolute text-sm text-slate-500 duration-300 transform -translate-y-4 scale-75 top-4 z-10 origin-[0] start-3 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-4 cursor-text">
        Buscar artículos por nombre o descripción...
    </label>
    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-400 group-focus-within:text-indigo-600 transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
    </div>
</div>
                <form id="form-bulk-enable" action="{{ route('admin.articulos.bulk-disponible') }}" method="POST" class="whitespace-nowrap flex-shrink-0">
                    @csrf
                    <input type="hidden" name="status" value="1">
                    <button type="button" onclick="confirmBulkAction('form-bulk-enable', '¿Seguro que deseas marcar TODOS los artículos con stock como disponibles?')" class="h-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-emerald-300 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-sm shadow-sm transition-all duration-200">
                        Activar Todos
                    </button>
                </form>
                <form id="form-bulk-disable" action="{{ route('admin.articulos.bulk-disponible') }}" method="POST" class="whitespace-nowrap flex-shrink-0">
                    @csrf
                    <input type="hidden" name="status" value="0">
                    <button type="button" onclick="confirmBulkAction('form-bulk-disable', '¿Seguro que deseas marcar TODOS los artículos como NO disponibles?')" class="h-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-rose-300 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold text-sm shadow-sm transition-all duration-200">
                        Desactivar Todos
                    </button>
                </form>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <div id="contenedor-tabla" class="mt-4 overflow-x-auto">
                        @include('admin.articulos._tabla', ['articulos' => $articulos])
                    </div>
                </div>
                <div class="p-4">
                    {{ $articulos->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nuevo Artículo -->
    <x-modal name="create-articulo">
        <div class="p-6">
            <div class="flex justify-between items-center pb-3 border-b mb-4">
                <h3 class="text-lg font-bold text-gray-900">Crear Nuevo Artículo</h3>
                <button type="button" onclick="closeModal('create-articulo')" class="text-gray-400 hover:text-gray-600 font-bold text-xl">&times;</button>
            </div>
            <form method="POST" action="{{ route('admin.articulos.store') }}" enctype="multipart/form-data">
                @csrf
                @include('admin.articulos._form', ['articulo' => new \App\Models\Articulo()])
            </form>
        </div>
    </x-modal>

    <!-- Modal Ventas Múltiples -->
    <x-modal name="ventas-multiples-modal">
        <div class="p-6">
            <div class="flex justify-between items-center pb-3 border-b mb-4">
                <h3 class="text-lg font-bold text-gray-900">Registrar Ventas Múltiples</h3>
                <button type="button" onclick="closeModal('ventas-multiples-modal')" class="text-gray-400 hover:text-gray-600 font-bold text-xl">&times;</button>
            </div>
            <form method="POST" action="{{ route('ventas.storeMultiple') }}" id="form-ventas-multiples">
                @csrf
                <input type="hidden" name="redirect_to" value="{{ route('admin.articulos.index') }}">
                
                <div id="ventas-container" class="space-y-4 max-h-[60vh] overflow-y-auto p-1">
                    <!-- Fila Molde -->
                    <div class="venta-item border p-4 rounded-lg bg-gray-50 relative">
                        <div class="flex justify-end mb-2">
                            <button type="button" class="btn-eliminar-venta p-1 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-full transition-colors cursor-pointer" title="Eliminar este ítem">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                        <div class="mb-3">
                            <label class="block font-bold text-gray-700 text-sm">Cliente</label>
                            <select name="ventas[0][user_id]" class="user-select-venta w-full border-gray-300 rounded-lg text-sm" required>
                                <option value="">Seleccione un cliente</option>
                                @foreach ($clientes as $cliente)
                                    <option value="{{ $cliente->id }}">{{ $cliente->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="block font-bold text-gray-700 text-sm">Artículo</label>
                            <select name="ventas[0][articulo_id]" class="articulo-select-venta w-full border-gray-300 rounded-lg text-sm" required>
                                <option value="">Seleccione un artículo...</option>
                                @foreach ($articulos as $articulo)
                                    @if($articulo->stock > 0)
                                        <option value="{{ $articulo->id }}" data-precio="{{ $articulo->precio }}" data-stock="{{ $articulo->stock }}">
                                            {{ $articulo->nombre }} (Stock: {{ $articulo->stock }})
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mb-3">
                            <div>
                                <label class="block font-bold text-gray-700 text-sm">Cantidad</label>
                                <input type="number" name="ventas[0][cantidad]" min="1" class="cantidad-input-venta w-full border-gray-300 rounded-lg text-sm" value="1" required>
                            </div>
                            <div>
                                <label class="block font-bold text-gray-700 text-sm">Precio Unit.</label>
                                <input type="number" step="0.01" name="ventas[0][precio]" class="precio-input-venta w-full border-gray-300 rounded-lg text-sm" required>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="block font-bold text-gray-700 text-sm">Descripción</label>
                            <textarea name="ventas[0][descripcion]" class="w-full border-gray-300 rounded-lg text-sm" rows="1"></textarea>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end mt-4">
                    <button type="button" id="btn-agregar-venta" class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-lg shadow-md transition-all cursor-pointer" title="Agregar Venta">+</button>
                </div>

                <div class="flex justify-end gap-3 mt-4 border-t pt-4">
                    <button type="button" onclick="closeModal('ventas-multiples-modal')" class="px-5 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-sm cursor-pointer">Cancelar</button>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-sm shadow-md cursor-pointer">Registrar Ventas</button>
                </div>
            </form>
        </div>
    </x-modal>
</x-app-layout>

<script>
    const buscador = document.getElementById('q');
    let timeout = null;

    buscador.addEventListener('keyup', function() {
        clearTimeout(timeout); // Limpiar búsqueda anterior
        const query = this.value;

        // Esperar 300ms después de dejar de escribir
        timeout = setTimeout(() => {
            fetch("{{ route('admin.articulos.index') }}?q=" + encodeURIComponent(query), {
                    headers: {
                        "X-Requested-With": "XMLHttpRequest"
                    }
                })
                .then(res => res.text())
                .then(html => {
                    document.getElementById('contenedor-tabla').innerHTML = html;
                });
        }, 300);
    });

    function confirmBulkAction(formId, message) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#4f46e5',
            cancelButtonColor: '#ef4444',
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById(formId).submit();
            }
        });
    }

    // Lógica para Ventas Múltiples
    (function() {
        const formVentas = document.getElementById('form-ventas-multiples');
        if (!formVentas) return;

        const container = document.getElementById('ventas-container');
        const btnAgregar = document.getElementById('btn-agregar-venta');
        let molde = container.querySelector('.venta-item').cloneNode(true);
        let index = 1;

        function actualizarPrecio(select) {
            const block = select.closest('.venta-item');
            if (!block) return;
            const precioInput = block.querySelector('.precio-input-venta');
            const option = select.options[select.selectedIndex];
            if (precioInput && option && option.dataset.precio) {
                precioInput.value = option.dataset.precio;
            } else if (precioInput) {
                precioInput.value = '';
            }
        }

        // Delegación de eventos para selects y botones
        container.addEventListener('change', (e) => {
            if (e.target.classList.contains('articulo-select-venta')) {
                actualizarPrecio(e.target);
            }
        });

        container.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-eliminar-venta');
            if (btn) {
                const items = container.querySelectorAll('.venta-item');
                if (items.length > 1) {
                    btn.closest('.venta-item').remove();
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Atención',
                        text: 'Debes registrar al menos una venta.',
                        timer: 2500,
                        showConfirmButton: false
                    });
                }
            }
        });

        btnAgregar.addEventListener('click', () => {
            const clone = molde.cloneNode(true);
            clone.querySelectorAll('input, textarea, select').forEach(el => {
                if (el.name) {
                    el.name = el.name.replace(/\[0\]/, `[${index}]`);
                }
                if (el.type === 'number') el.value = el.classList.contains('cantidad-input-venta') ? 1 : '';
                else el.value = '';
            });
            container.appendChild(clone);
            index++;
        });
    })();
</script>
