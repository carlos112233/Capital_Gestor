<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Catálogo de Artículos') }}
            </h2>
            @if(Auth::user()->hasRole('admin'))
            <button type="button" onclick="openModal('ventas-multiples-modal')"
                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-sm shadow-md shadow-emerald-500/25 hover:shadow-lg hover:shadow-emerald-500/35 transition-all duration-200 transform hover:-translate-y-0.5 focus:outline-none cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                {{ __('Ventas Múltiples') }}
            </button>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-8 lg:px-24">

            <!-- Mensajes -->
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded" role="alert">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded" role="alert">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded" role="alert">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Buscador (GET) -->
            <form method="GET" action="{{ route('catalogo.index') }}" class="mb-6 flex gap-2">
                <div class="relative w-full group">
    <input type="text" name="q" id="q" value="{{ request('q') }}" class="block rounded-t-lg px-3 pb-2 pt-6 w-full text-sm text-slate-800 bg-slate-100 border-0 border-b-2 border-slate-300 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer pr-10 transition-colors focus:bg-slate-200/50" placeholder=" " autocomplete="off" />
    <label for="q" class="absolute text-sm text-slate-500 duration-300 transform -translate-y-4 scale-75 top-4 z-10 origin-[0] start-3 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-4 cursor-text">
        Buscar por nombre…
    </label>
    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-400 group-focus-within:text-indigo-600 transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
    </div>
</div>
            </form>

            <!-- Contenedor que se reemplaza por AJAX -->
            <div id="catalogo-content">
                @include('catalogo.partials.grid', ['articulos' => $articulos])
            </div>
        </div>
    </div>

    <!-- Modal Vender / Comprar Artículo -->
    <x-modal name="vender-modal">
        <div class="p-6 text-left">
            <div class="flex justify-between items-center pb-3 border-b mb-4">
                <h3 class="text-lg font-bold text-gray-900" id="modal_vender_title">
                    @if (Auth::user()->hasRole('admin')) Registrar Venta @else Realizar Compra @endif
                </h3>
                <button type="button" onclick="closeModal('vender-modal')" class="text-gray-400 hover:text-gray-600 font-bold text-xl">&times;</button>
            </div>
            <form method="POST" action="{{ route('ventas.store') }}">
                @csrf
                <input type="hidden" name="redirect_to" value="{{ route('catalogo.index') }}">
                <input type="hidden" name="articulo_id" id="modal_vender_articulo_id">

                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2">Artículo</label>
                    <input type="text" id="modal_vender_articulo_nombre" class="w-full border-gray-300 rounded-lg shadow-sm bg-gray-100 font-semibold" readonly>
                </div>

                @if (Auth::user()->hasRole('admin'))
                    <div class="mb-4">
                        <label class="block text-gray-700 font-bold mb-2">Cliente</label>
                        <select name="cliente_id" id="modal_vender_cliente_id" class="searchable-select w-full border-gray-300 rounded-lg shadow-sm" required>
                            <option value="" disabled selected>Seleccione un cliente</option>
                            @foreach($clientes as $cliente)
                                <option value="{{ $cliente->id }}">{{ $cliente->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Cantidad</label>
                        <input type="number" name="cantidad" id="modal_vender_cantidad" class="w-full border-gray-300 rounded-lg shadow-sm" value="1" min="1" required>
                        <span class="text-xs text-gray-500 mt-1 block" id="modal_vender_stock_info"></span>
                    </div>

                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Precio Unitario ($)</label>
                        <input type="number" step="0.01" name="precio_venta" id="modal_vender_precio" class="w-full border-gray-300 rounded-lg shadow-sm" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2">Descripción / Notas</label>
                    <textarea name="descripcion" id="modal_vender_descripcion" class="block w-full border-gray-300 rounded-md shadow-sm" rows="2" placeholder="Notas adicionales de la venta..."></textarea>
                </div>

                <div class="flex justify-end gap-3 mt-4 border-t pt-4">
                    <button type="button" onclick="closeModal('vender-modal')" class="px-5 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-sm cursor-pointer">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-sm shadow-md cursor-pointer">
                        Confirmar Venta
                    </button>
                </div>
            </form>
        </div>
    </x-modal>

    @if(Auth::user()->hasRole('admin'))
    <!-- Modal Ventas Múltiples (Para Admin en Catálogo) -->
    <x-modal name="ventas-multiples-modal">
        <div class="p-6">
            <div class="flex justify-between items-center pb-3 border-b mb-4">
                <h3 class="text-lg font-bold text-gray-900">Registrar Ventas Múltiples</h3>
                <button type="button" onclick="closeModal('ventas-multiples-modal')" class="text-gray-400 hover:text-gray-600 font-bold text-xl">&times;</button>
            </div>
            <form method="POST" action="{{ route('ventas.storeMultiple') }}" id="form-ventas-multiples">
                @csrf
                <input type="hidden" name="redirect_to" value="{{ route('catalogo.index') }}">
                
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
    @endif

    <!-- Búsqueda en vivo (vanilla JS + debounce) -->
    <script>
        @if(Auth::user()->hasRole('admin'))
        // Lógica para Ventas Múltiples (Catálogo)
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
                        if(typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Atención',
                                text: 'Debes registrar al menos una venta.',
                                timer: 2500,
                                showConfirmButton: false
                            });
                        }
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
        @endif

        function openVentaModal(articuloId, articuloNombre, precio, stock) {
            document.getElementById('modal_vender_articulo_id').value = articuloId;
            document.getElementById('modal_vender_articulo_nombre').value = articuloNombre;
            document.getElementById('modal_vender_precio').value = parseFloat(precio).toFixed(2);
            
            const cantInput = document.getElementById('modal_vender_cantidad');
            cantInput.value = 1;
            cantInput.max = stock;
            
            const stockInfo = document.getElementById('modal_vender_stock_info');
            if (stockInfo) stockInfo.innerText = `Disponible: ${stock} pza(s)`;

            openModal('vender-modal');
        }
        (function () {
            const input = document.getElementById('q');
            const container = document.getElementById('catalogo-content');
            let controller;

            function debounce(fn, delay) {
                let t;
                return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), delay); };
            }

            const fetchResults = debounce(async (value) => {
                try {
                    if (controller) controller.abort();
                    controller = new AbortController();

                    const url = new URL("{{ route('catalogo.index') }}", window.location.origin);
                    if (value) url.searchParams.set('q', value);
                    url.searchParams.set('ajax', '1');

                    const res = await fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        signal: controller.signal
                    });
                    if (!res.ok) return;
                    const html = await res.text();
                    container.innerHTML = html;
                } catch (e) {
                    // Silencioso: usuario puede seguir escribiendo
                }
            }, 300);

            if (input) {
                input.addEventListener('input', (e) => fetchResults(e.target.value));
            }

            // Soporte para paginación vía AJAX dentro del contenedor
            container.addEventListener('click', (e) => {
                const a = e.target.closest('a[href]');
                if (!a) return;

                // Interceptar enlaces de paginación
                if (a.href.includes('page=')) {
                    e.preventDefault();
                    const url = new URL(a.href);
                    url.searchParams.set('ajax', '1');
                    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(r => r.text())
                        .then(html => container.innerHTML = html)
                        .catch(() => {});
                }
            });
        })();
    </script>
</x-app-layout>
