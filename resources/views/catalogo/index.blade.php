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
                <input
                    id="q"
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Buscar por nombre…"
                    autocomplete="off"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring focus:ring-indigo-200"
                >
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
                        <select name="cliente_id" id="modal_vender_cliente_id" class="w-full border-gray-300 rounded-lg shadow-sm" required>
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
