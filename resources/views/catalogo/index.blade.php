<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Catálogo de Artículos') }}
        </h2>
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

    <!-- Búsqueda en vivo (vanilla JS + debounce) -->
    <script>
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
