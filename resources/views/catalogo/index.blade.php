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

    <!-- Búsqueda en vivo (vanilla JS + debounce) -->
    <script>
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
