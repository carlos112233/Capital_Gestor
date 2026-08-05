{{-- resources/views/ventas/_form.blade.php --}}
@php
    $articuloId = $articuloId ?? null;
@endphp

<div class="mb-4">
    <label class="block text-gray-700 font-bold mb-2">Artículo</label>
    <select name="articulo_id" class="searchable-select w-full border-gray-300 rounded-lg shadow-sm" required>
        <option value="">Seleccione un artículo</option>
        @foreach ($articulos as $articulo)
            @if($articulo->nombre != "Pago saldado")
            <option value="{{ $articulo->id }}"
                {{ old('articulo_id', $venta->articulo_id ?? $articuloId ?? '') == $articulo->id ? 'selected' : '' }}>
                {{ $articulo->nombre }}
            </option>
            @endif
        @endforeach
    </select>
    @error('articulo_id')
        <span class="text-red-600 text-sm">{{ $message }}</span>
    @enderror
</div>

<div class="mb-4">
    <label class="block text-gray-700 font-bold mb-2">Precio de Venta</label>
    <input type="number" step="0.01" name="precio_venta"
        class="w-full border-gray-300 rounded-lg shadow-sm"
        value="{{ old('precio_venta', $venta->precio_venta ?? ($articuloId ? $articulos->find($articuloId)->precio : '')) }}" required>
    @error('precio_venta')
        <span class="text-red-600 text-sm">{{ $message }}</span>
    @enderror
</div>

<div class="mb-4">
    <label class="block text-gray-700 font-bold mb-2">Cantidad</label>
    <input type="number" name="cantidad" class="w-full border-gray-300 rounded-lg shadow-sm"
        value="{{ old('cantidad', $venta->cantidad ?? 1) }}" required step="1">
    @error('cantidad')
        <span class="text-red-600 text-sm">{{ $message }}</span>
    @enderror
</div>

@if (Auth::user()->hasRole('admin'))
    <div class="mb-4">
        <label class="block text-gray-700 font-bold mb-2">Cliente</label>
        <select name="cliente_id" class="searchable-select w-full border-gray-300 rounded-lg shadow-sm" required>
            <option value="">Seleccione un cliente</option>
            @foreach ($clientes as $cliente)
                <option value="{{ $cliente->id }}" 
                    {{ (old('cliente_id', $venta->user_id ?? '') == $cliente->id) ? 'selected' : '' }}>
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
    <label class="block text-gray-700 font-bold mb-2">Descripción del pedido</label>
    <textarea name="descripcion"
        class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('descripcion', $venta->descripcion ?? '') }}</textarea>
    @error('descripcion')
        <span class="text-red-600 text-sm">{{ $message }}</span>
    @enderror
</div>

<div class="flex justify-end gap-3 mt-4 border-t border-slate-100 pt-4">
    <button type="button" x-data x-on:click="$dispatch('close-modal', 'create-venta'); $dispatch('close-modal', 'edit-venta')"
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
    const precios = {
        @foreach($articulos as $articulo)
            "{{ $articulo->id }}": {{ $articulo->precio ?? 0 }}@if(!$loop->last),@endif
        @endforeach
    };

    const currentScript = document.currentScript;
    const form = currentScript ? currentScript.closest('form') : document;
    if (!form) return;

    const articuloSelect = form.querySelector('[name="articulo_id"]');
    const precioInput = form.querySelector('[name="precio_venta"]');

    if (articuloSelect && precioInput) {
        articuloSelect.addEventListener('change', function() {
            const selectedId = this.value;
            if (precios[selectedId]) {
                precioInput.value = parseFloat(precios[selectedId]).toFixed(2);
            }
        });
    }
})();
</script>
