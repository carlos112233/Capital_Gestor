{{-- resources/views/admin/entradas/_form.blade.php --}}

@php
    $esSaldar = request('saldar') == 1;
    $presetCliente = request('cliente_id');
    $presetSaldo = request('saldo');
@endphp

<div class="mb-4">
    <label for="tipo_pago" class="block text-gray-700 font-bold mb-2">Tipo de Pago</label>
    <select name="tipo_pago" class="searchable-select tipo_pago_select w-full border-gray-300 rounded-lg shadow-sm" required>
        <option value="" disabled {{ !isset($entrada->id) && !$esSaldar ? 'selected' : '' }}>Seleccione un tipo</option>
        <option value="1" {{ (isset($entrada->id) && optional($entrada->articulo)->nombre != 'Pago saldado') ? 'selected' : '' }}>Por artículo</option>
        <option value="2" {{ $esSaldar || (isset($entrada->id) && optional($entrada->articulo)->nombre == 'Pago saldado') ? 'selected' : '' }}>Saldar adeudo</option>
    </select>
</div>

<div class="mb-4">
    <label for="articulo_id" class="block text-gray-700 font-bold mb-2">Artículo</label>
    <select name="articulo_id" class="searchable-select articulo_id_select w-full border-gray-300 rounded-lg shadow-sm" required>
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
    <select name="cliente_id" class="searchable-select cliente_id_select w-full border-gray-300 rounded-lg shadow-sm" required>
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
              class="descripcion_input block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ $esSaldar ? 'Pago saldado' : old('descripcion', $entrada->descripcion ?? '') }}</textarea>
</div>

@if (Auth::user()->hasRole('admin'))
<div class="mb-4 flex items-center">
    <input type="hidden" name="enviar_wa" value="0">
    <input type="checkbox" name="enviar_wa" id="form_enviar_wa" value="1" checked class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 w-4 h-4 cursor-pointer">
    <label for="form_enviar_wa" class="ml-2 block text-sm text-gray-700 font-medium cursor-pointer">
        Enviar notificación de pago por WhatsApp al cliente
    </label>
</div>
@endif

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
                    let saldadoVal = null;
                    let firstRegularVal = null;

                    Array.from(articuloSelect.options).forEach(opt => {
                        if (opt.value === "") return;
                        const isSaldado = opt.textContent.trim().toLowerCase() === 'pago saldado';

                        if (val === "2") { // Saldar adeudo
                            if (isSaldado) {
                                opt.hidden = false;
                                opt.disabled = false;
                                saldadoVal = opt.value;
                                if (articuloSelect.tomselect) articuloSelect.tomselect.enableOption(opt.value);
                            } else {
                                opt.hidden = true;
                                opt.disabled = true;
                                if (articuloSelect.tomselect) articuloSelect.tomselect.disableOption(opt.value);
                            }
                        } else if (val === "1") { // Por artículo
                            if (isSaldado) {
                                opt.hidden = true;
                                opt.disabled = true;
                                if (articuloSelect.tomselect) articuloSelect.tomselect.disableOption(opt.value);
                            } else {
                                opt.hidden = false;
                                opt.disabled = false;
                                if (!firstRegularVal) firstRegularVal = opt.value;
                                if (articuloSelect.tomselect) articuloSelect.tomselect.enableOption(opt.value);
                            }
                        }
                    });

                    if (val === "2" && saldadoVal) {
                        if (articuloSelect.tomselect) {
                            articuloSelect.tomselect.setValue(saldadoVal);
                        } else {
                            articuloSelect.value = saldadoVal;
                        }
                    }
                }

                function updateDescription() {
                    const descInput = form.querySelector('.descripcion_input');
                    if (!descInput) return;
                    
                    if (tipoPago.value === '2') {
                        if (!descInput.value || descInput.value === "Pagar saldo") {
                            descInput.value = "Pago saldado";
                        }
                    } else if (tipoPago.value === '1') {
                        if (descInput.value === "Pagar saldo" || descInput.value === "Pago saldado") {
                            descInput.value = "";
                        }
                    }
                }

                tipoPago.addEventListener('change', function() {
                    updateArticulos();
                    updateDescription();
                    
                    if (articuloSelect.tomselect && tipoPago.value === '1') {
                        articuloSelect.tomselect.setValue('');
                    }

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
                    updateDescription();
                    let attempts = 0;
                    let checkTs = setInterval(function() {
                        if (articuloSelect.tomselect) {
                            updateArticulos();
                            clearInterval(checkTs);
                        }
                        if (++attempts > 20) clearInterval(checkTs);
                    }, 100);
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