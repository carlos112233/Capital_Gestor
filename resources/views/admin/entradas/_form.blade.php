{{-- resources/views/admin/entradas/_form.blade.php --}}

@php
    $esSaldar = request('saldar') == 1;
    $presetCliente = request('cliente_id');
    $presetSaldo = request('saldo');
@endphp

<div class="mb-4">
    <label for="tipo_pago" class="block text-gray-700 font-bold mb-2">Tipo de Pago</label>
    <select name="tipo_pago" class="tipo_pago_select w-full border-gray-300 rounded-lg shadow-sm" required>
        <option value="" disabled {{ !isset($entrada->id) && !$esSaldar ? 'selected' : '' }}>Seleccione un tipo</option>
        <option value="1" {{ (isset($entrada->id) && optional($entrada->articulo)->nombre != 'Pago saldado') ? 'selected' : '' }}>Por artículo</option>
        <option value="2" {{ $esSaldar || (isset($entrada->id) && optional($entrada->articulo)->nombre == 'Pago saldado') ? 'selected' : '' }}>Saldar adeudo</option>
    </select>
</div>

<div class="mb-4">
    <label for="articulo_id" class="block text-gray-700 font-bold mb-2">Artículo</label>
    <select name="articulo_id" class="articulo_id_select w-full border-gray-300 rounded-lg shadow-sm" required>
        <option value="" disabled {{ !$esSaldar && !old('articulo_id', $entrada->articulo_id ?? '') ? 'selected' : '' }}>Seleccione un artículo</option>
        @foreach($articulos as $art)
            @php
                $esPagoSaldado = strtolower($art->nombre) === 'pago saldado';
                $isSelected = old('articulo_id', $entrada->articulo_id ?? '') == $art->id || ($esSaldar && $esPagoSaldado);
            @endphp
            <option value="{{ $art->id }}" data-precio="{{ $art->precio }}" {{ $isSelected ? 'selected' : '' }}>
                {{ $art->nombre }}
            </option>
        @endforeach
    </select>
    @error('articulo_id')
        <span class="text-red-600 text-sm">{{ $message }}</span>
    @enderror
</div>

<div class="mb-4">
    <label for="precio_venta" class="block text-gray-700 font-bold mb-2">Precio de Venta</label>
    <input type="number" step="0.01" name="precio_venta" class="precio_venta_input w-full border-gray-300 rounded-lg shadow-sm"
           value="{{ $esSaldar ? $presetSaldo : (isset($entrada->precio_venta) ? number_format($entrada->precio_venta, 2, '.', '') : '') }}" required>
    @error('precio_venta')
        <span class="text-red-600 text-sm">{{ $message }}</span>
    @enderror
</div>

@if (Auth::user()->hasRole('admin'))
<div class="mb-4">
    <label for="cliente_id" class="block text-gray-700 font-bold mb-2">Cliente</label>
    <select name="cliente_id" class="cliente_id_select w-full border-gray-300 rounded-lg shadow-sm" required>
        <option value="" disabled {{ !$esSaldar && old('cliente_id', $entrada->cliente_id ?? $entrada->user_id ?? '') == '' ? 'selected' : '' }}>Seleccione un cliente</option>
        @foreach($users as $cliente)
            <option value="{{ $cliente->id }}" 
                {{ ($esSaldar && $presetCliente == $cliente->id) || old('cliente_id', $entrada->cliente_id ?? $entrada->user_id ?? '') == $cliente->id ? 'selected' : '' }}>
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
    <textarea name="descripcion"
              class="descripcion_input block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ $esSaldar ? 'Saldar adeudo pendiente' : old('descripcion', $entrada->descripcion ?? '') }}</textarea>
</div>

<div class="flex justify-end gap-3 mt-4 border-t border-slate-100 pt-4">
    <button type="button" x-data x-on:click="$dispatch('close-modal', 'create-entrada'); $dispatch('close-modal', 'edit-entrada')"
        class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-sm shadow-sm transition-all duration-200 hover:border-slate-400 focus:outline-none cursor-pointer">
        Cancelar
    </button>
    <button type="submit" 
        class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-sm shadow-md shadow-indigo-500/25 hover:shadow-lg hover:shadow-indigo-500/35 transition-all duration-200 transform hover:-translate-y-0.5 focus:outline-none cursor-pointer">
        Guardar
    </button>
</div>

<script>
    (function() {
        function initFormLogic() {
            const tipoPagoSelects = document.querySelectorAll('.tipo_pago_select');
            tipoPagoSelects.forEach(tipoPago => {
                const form = tipoPago.closest('form');
                if (!form || form.dataset.initialized) return;
                form.dataset.initialized = 'true';

                const articuloSelect = form.querySelector('.articulo_id_select');
                const precioInput = form.querySelector('.precio_venta_input');
                if (!articuloSelect) return;

                function updateArticulos() {
                    const val = tipoPago.value;
                    let saldadoOption = null;
                    let firstRegularOption = null;

                    Array.from(articuloSelect.options).forEach(opt => {
                        if (opt.value === "") return;
                        const isSaldado = opt.textContent.trim().toLowerCase() === 'pago saldado';

                        if (val === "2") { // Saldar adeudo
                            if (isSaldado) {
                                opt.hidden = false;
                                opt.disabled = false;
                                opt.selected = true;
                                saldadoOption = opt;
                            } else {
                                opt.hidden = true;
                                opt.disabled = true;
                                opt.selected = false;
                            }
                        } else if (val === "1") { // Por artículo
                            if (isSaldado) {
                                opt.hidden = true;
                                opt.disabled = true;
                                opt.selected = false;
                            } else {
                                opt.hidden = false;
                                opt.disabled = false;
                                if (!firstRegularOption) firstRegularOption = opt;
                            }
                        }
                    });

                    if (val === "2" && saldadoOption) {
                        saldadoOption.selected = true;
                    }
                }

                tipoPago.addEventListener('change', function() {
                    updateArticulos();
                    if (tipoPago.value === '1' && articuloSelect.selectedIndex > 0) {
                        const selOpt = articuloSelect.options[articuloSelect.selectedIndex];
                        if (selOpt && selOpt.dataset.precio && precioInput) {
                            precioInput.value = parseFloat(selOpt.dataset.precio).toFixed(2);
                        }
                    }
                });

                articuloSelect.addEventListener('change', function() {
                    const selOpt = articuloSelect.options[articuloSelect.selectedIndex];
                    if (selOpt && selOpt.dataset.precio && tipoPago.value === '1' && precioInput) {
                        precioInput.value = parseFloat(selOpt.dataset.precio).toFixed(2);
                    }
                });

                if (tipoPago.value) {
                    updateArticulos();
                }
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initFormLogic);
        } else {
            initFormLogic();
        }
    })();
</script>