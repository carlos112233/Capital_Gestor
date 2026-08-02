{{-- resources/views/entradas/_form.blade.php --}}

@php
    // Detectamos si venimos del botón de "Saldar adeudo"
    $esSaldar = request('saldar') == 1;
    $presetCliente = request('cliente_id');
    $presetSaldo = request('saldo');
@endphp

<div class="mb-4">
    <label for="tipo_pago" class="block text-gray-700 font-bold mb-2">Tipo de Pago</label>
    <select name="tipo_pago" id="tipo_pago" class="w-full border-gray-300 rounded-lg shadow-sm" required>
        <option value="" disabled {{ !isset($entrada->id) && !$esSaldar ? 'selected' : '' }}>Seleccione un tipo</option>
        <option value="1" {{ (isset($entrada->id) && optional($entrada->articulo)->nombre != 'Pago saldado') ? 'selected' : '' }}>Por artículo</option>
        {{-- Si esSaldar es true, seleccionamos esta opción por defecto --}}
        <option value="2" {{ $esSaldar || (isset($entrada->id) && optional($entrada->articulo)->nombre == 'Pago saldado') ? 'selected' : '' }}>Saldar adeudo</option>
    </select>
</div>

<div class="mb-4">
    <label for="articulo_id" class="block text-gray-700 font-bold mb-2">Artículo</label>
    <select name="articulo_id" id="articulo_id" class="w-full border-gray-300 rounded-lg shadow-sm" required>
        <option value="" disabled>Seleccione un artículo</option>
        {{-- Las opciones se llenan por JS, pero el JS usará el valor 'selected' que definamos --}}
    </select>
    @error('articulo_id')
        <span class="text-red-600 text-sm">{{ $message }}</span>
    @enderror
</div>

<div class="mb-4">
    <label for="precio_venta" class="block text-gray-700 font-bold mb-2">Precio de Venta</label>
    <input type="number" step="1" name="precio_venta" id="precio_venta"
           class="w-full border-gray-300 rounded-lg shadow-sm"
           {{-- Prioridad: 1. Saldo de la URL, 2. Valor de la entrada existente, 3. Vacío --}}
           value="{{ $esSaldar ? $presetSaldo : (isset($entrada) ? number_format($entrada->precio_venta, 0, '.', '') : '') }}" required>
    @error('precio_venta')
        <span class="text-red-600 text-sm">{{ $message }}</span>
    @enderror
</div>

@if (Auth::user()->hasRole('admin'))
<div class="mb-4">
    <label for="cliente_id" class="block text-gray-700 font-bold mb-2">Cliente</label>
    <select name="cliente_id" id="cliente_id" class="w-full border-gray-300 rounded-lg shadow-sm" required>
        <option value="" disabled {{ !$esSaldar && old('cliente_id', $entrada->user_id ?? '') == '' ? 'selected' : '' }}>Seleccione un cliente</option>
        @foreach($users as $cliente)
            <option value="{{ $cliente->id }}" 
                {{-- Si esSaldar, comparamos con el ID enviado por URL --}}
                {{ ($esSaldar && $presetCliente == $cliente->id) || old('cliente_id', $entrada->user_id ?? '') == $cliente->id ? 'selected' : '' }}>
                {{ $cliente->name }}
            </option>
        @endforeach
    </select>
    @error('cliente_id')
        <span class="text-red-600 text-sm">{{ $message }}</span>
    @enderror
</div>
@endif

<div class="mb-4">
    <label for="descripcion" class="block text-gray-700 font-bold mb-2">Descripción del pedido</label>
    <textarea id="descripcion" name="descripcion"
              class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ $esSaldar ? 'Saldar adeudo pendiente' : old('descripcion', $entrada->descripcion ?? '') }}</textarea>
</div>
<div class="flex justify-end gap-3 mt-4 border-t border-slate-100 pt-4">
    <button type="button" x-data x-on:click="$dispatch('close-modal', 'create-entrada'); $dispatch('close-modal', 'edit-entrada-{{ $entrada->id ?? 0 }}')"
        class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-sm shadow-sm transition-all duration-200 hover:border-slate-400 focus:outline-none cursor-pointer">
        Cancelar
    </button>
    <button type="submit" 
        class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-sm shadow-md shadow-indigo-500/25 hover:shadow-lg hover:shadow-indigo-500/35 transition-all duration-200 transform hover:-translate-y-0.5 focus:outline-none cursor-pointer">
        Guardar
    </button>
</div>
{{-- BOTONES IGUALES... --}}

<script>
(function() {
    const articulos = @json($articulos ?? []);
    const currentScript = document.currentScript;
    const form = currentScript ? currentScript.closest('form') : document;
    if (!form) return;

    const tipoPago = form.querySelector('[name="tipo_pago"]');
    const articuloSelect = form.querySelector('[name="articulo_id"]');
    const precioInput = form.querySelector('[name="precio_venta"]');
    
    if (!tipoPago || !articuloSelect || !precioInput) return;

    let selectedArticuloId = "{{ $entrada->articulo_id ?? '' }}";
    const esSaldarFlujo = "{{ $esSaldar }}" == "1";

    function actualizarListaArticulos() {
        const tipo = tipoPago.value;
        articuloSelect.innerHTML = '<option value="" disabled selected>Seleccione un artículo</option>';
        
        const filtrados = articulos.filter(art => {
            const esSaldarPago = art.nombre.toLowerCase() === 'pago saldado';
            return (tipo === '2') ? esSaldarPago : !esSaldarPago;
        });

        filtrados.forEach(art => {
            const opt = document.createElement('option');
            opt.value = art.id;
            opt.textContent = art.nombre;
            opt.dataset.precio = art.precio;
            
            if (esSaldarFlujo && art.nombre.toLowerCase() === 'pago saldado') {
                opt.selected = true;
            } else if (art.id == selectedArticuloId) {
                opt.selected = true;
            }
            
            articuloSelect.appendChild(opt);
        });
    }

    tipoPago.addEventListener('change', function() {
        actualizarListaArticulos();
        if (!esSaldarFlujo && articuloSelect.options.length > 1) {
            const primerArt = articulos.find(a => a.id == articuloSelect.value);
            if (primerArt) precioInput.value = parseFloat(primerArt.precio).toFixed(2);
        }
    });

    articuloSelect.addEventListener('change', function() {
        const art = articulos.find(a => a.id == this.value);
        if (art && !esSaldarFlujo) {
            precioInput.value = parseFloat(art.precio).toFixed(2);
        }
    });

    actualizarListaArticulos();
})();
</script>