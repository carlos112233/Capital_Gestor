<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Mis Entradas de Capital') }}
            </h2>
            <button type="button" onclick="openCreateModal()"
                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-sm shadow-md shadow-indigo-500/25 hover:shadow-lg hover:shadow-indigo-500/35 transition-all duration-200 transform hover:-translate-y-0.5 focus:outline-none cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('Nueva entrada') }}
            </button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form method="GET" action="{{ route('admin.entradas.index') }}" class="mb-6 flex gap-2">
                <div class="relative z-0 w-full group">
    <input type="text" name="q" id="q" value="{{ request('q') }}" class="block py-2.5 px-0 w-full text-sm text-slate-800 bg-transparent border-0 border-b-2 border-slate-300 appearance-none focus:outline-none focus:ring-0 focus:border-indigo-600 peer" placeholder=" " autocomplete="off" />
    <label for="q" class="peer-focus:font-medium absolute text-sm text-slate-500 duration-300 transform -translate-y-6 scale-75 top-3 -z-10 origin-[0] peer-focus:start-0 rtl:peer-focus:translate-x-1/4 peer-focus:text-indigo-600 peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0 peer-focus:scale-75 peer-focus:-translate-y-6 flex items-center gap-2 cursor-text">
        <svg class="w-4 h-4 text-slate-400 group-focus-within:text-indigo-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        Buscar por usuario, artículo o concepto...
    </label>
</div>
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <div id="contenedor-tabla" class="mt-4 overflow-x-auto">
                        @include('admin.entradas._tabla', ['entradas' => $entradas])
                    </div>
                </div>
                <div class="p-4">
                    {{ $entradas->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nueva Entrada -->
    <x-modal name="create-entrada">
        <div class="p-6">
            <div class="flex justify-between items-center pb-3 border-b mb-4">
                <h3 class="text-lg font-bold text-gray-900">Registrar Nueva Entrada de Capital</h3>
                <button type="button" onclick="closeModal('create-entrada')" class="text-gray-400 hover:text-gray-600 font-bold text-xl">&times;</button>
            </div>
            <form id="form-create-entrada" method="POST" action="{{ route('admin.entradas.store') }}">
                @csrf
                @include('admin.entradas._form', ['entrada' => new \App\Models\Entrada()])
            </form>
        </div>
    </x-modal>

    <!-- Modal Editar Entrada Unificado -->
    <x-modal name="edit-entrada">
        <div class="p-6 text-left">
            <div class="flex justify-between items-center pb-3 border-b mb-4">
                <h3 id="edit-modal-title" class="text-lg font-bold text-gray-900">Editar Entrada de Capital</h3>
                <button type="button" onclick="closeModal('edit-entrada')" class="text-gray-400 hover:text-gray-600 font-bold text-xl">&times;</button>
            </div>
            <form id="form-edit-entrada" method="POST" action="">
                @csrf
                @method('PUT')
                @include('admin.entradas._form', ['entrada' => new \App\Models\Entrada()])
            </form>
        </div>
    </x-modal>
</x-app-layout>

<script>
    const articulos = @json($articulos ?? []);

    function openCreateModal() {
        const form = document.getElementById('form-create-entrada');
        if (form) {
            setupFormBehavior(form, null);
        }
        openModal('create-entrada');
    }

    function openEditModal(entrada) {
        const form = document.getElementById('form-edit-entrada');
        const title = document.getElementById('edit-modal-title');
        if (form && entrada) {
            form.action = "{{ url('admin/entradas') }}/" + entrada.id;
            if (title) title.textContent = "Editar Entrada #" + entrada.id;
            setupFormBehavior(form, entrada);
        }
        openModal('edit-entrada');
    }

    function setupFormBehavior(form, entradaData) {
        const tipoPago = form.querySelector('.tipo_pago_select');
        const articuloSelect = form.querySelector('.articulo_id_select');
        const precioInput = form.querySelector('.precio_venta_input');
        const clienteSelect = form.querySelector('.cliente_id_select');
        const descripcionInput = form.querySelector('.descripcion_input');

        if (!tipoPago || !articuloSelect) return;

        const isEdit = !!entradaData;
        const currentArtId = isEdit ? entradaData.articulo_id : null;
        const isSaldar = isEdit && (entradaData.articulo && entradaData.articulo.nombre.toLowerCase() === 'pago saldado');

        if (isEdit) {
            tipoPago.value = isSaldar ? '2' : '1';
            if (precioInput) precioInput.value = parseFloat(entradaData.precio_venta || 0).toFixed(2);
            if (clienteSelect) clienteSelect.value = entradaData.cliente_id || entradaData.user_id || '';
            if (descripcionInput) descripcionInput.value = entradaData.descripcion || '';
        }

        function populateArticles() {
            const tipo = tipoPago.value;
            articuloSelect.innerHTML = '<option value="" disabled selected>Seleccione un artículo</option>';

            const filtrados = articulos.filter(art => {
                const esSaldar = art.nombre.toLowerCase() === 'pago saldado';
                return (tipo === '2') ? esSaldar : !esSaldar;
            });

            filtrados.forEach(art => {
                const opt = document.createElement('option');
                opt.value = art.id;
                opt.textContent = art.nombre;
                opt.dataset.precio = art.precio;

                if (art.id == currentArtId || (tipo === '2' && art.nombre.toLowerCase() === 'pago saldado')) {
                    opt.selected = true;
                }
                articuloSelect.appendChild(opt);
            });
        }

        tipoPago.onchange = function() {
            populateArticles();
            if (precioInput && tipoPago.value === '1' && articuloSelect.options.length > 1) {
                const selOpt = articuloSelect.options[articuloSelect.selectedIndex];
                if (selOpt && selOpt.dataset.precio) {
                    precioInput.value = parseFloat(selOpt.dataset.precio).toFixed(2);
                }
            }
        };

        articuloSelect.onchange = function() {
            const selOpt = articuloSelect.options[articuloSelect.selectedIndex];
            if (selOpt && selOpt.dataset.precio && tipoPago.value === '1' && precioInput) {
                precioInput.value = parseFloat(selOpt.dataset.precio).toFixed(2);
            }
        };

        populateArticles();
    }

    const buscador = document.getElementById('q');
    let timeout = null;

    if (buscador) {
        buscador.addEventListener('keyup', function() {
            clearTimeout(timeout);
            const query = this.value;

            timeout = setTimeout(() => {
                fetch("{{ route('admin.entradas.index') }}?q=" + encodeURIComponent(query), {
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
    }
</script>
