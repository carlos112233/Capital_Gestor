{{-- resources/views/ventas/_form.blade.php --}}

<div class="mb-4">
    <label for="articulo_id" class="block text-gray-700 font-bold mb-2">Artículo</label>
    <select name="articulo_id" id="articulo_id" class="w-full border-gray-300 rounded-lg shadow-sm">
        <option value="">Seleccione un artículo</option>
        @foreach ($articulos as $articulo)
            <option value="{{ $articulo->id }}"
                {{ old('articulo_id', $venta->articulo_id ?? '') == $articulo->id ? 'selected' : '' }}>
                {{ $articulo->nombre }}
            </option>
        @endforeach
    </select>
    @error('articulo_id')
        <span class="text-red-600 text-sm">{{ $message }}</span>
    @enderror
</div>

<div class="mb-4">
    <label for="precio_venta" class="block text-gray-700 font-bold mb-2">Precio de Venta</label>
    <input type="number" step="0.01" name="precio_venta" id="precio_venta"
        class="w-full border-gray-300 rounded-lg shadow-sm"
        value="{{ old('precio_venta', $venta->precio_venta ?? '') }}">
    @error('precio_venta')
        <span class="text-red-600 text-sm">{{ $message }}</span>
    @enderror
</div>

<div class="mb-4">
    <label for="cantidad" class="block text-gray-700 font-bold mb-2">Cantidad</label>
    <input type="number" name="cantidad" id="cantidad" class="w-full border-gray-300 rounded-lg shadow-sm"
        value="{{ old('cantidad', $venta->cantidad ?? '') }}" required step="1">
    @error('cantidad')
        <span class="text-red-600 text-sm">{{ $message }}</span>
    @enderror
</div>

<div class="mb-4">
    <label for="cliente_id" class="block text-gray-700 font-bold mb-2">Cliente</label>
    <select name="cliente_id" id="cliente_id" class="w-full border-gray-300 rounded-lg shadow-sm">
        <option value="">Seleccione un cliente</option>
        @if ($clientes != null)
            @foreach ($clientes as $cliente)
                <option value="{{ $cliente->id }}"
                    {{ old('cliente_id', $cliente->cliente_id ?? '') == $cliente->id ? 'selected' : '' }}>
                    {{ $cliente->nombre }}
                </option>
            @endforeach
        @endif
    </select>
    @error('cliente_id')
        <span class="text-red-600 text-sm">{{ $message }}</span>
    @enderror
</div>

<div class="mb-4">
    <label for="descripcion" class="block text-gray-700 font-bold mb-2">Descripción del pedido</label>
    <textarea id="descripcion" name="descripcion"
        class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('descripcion', $venta->descripcion ?? '') }}</textarea>
    @error('descripcion')
        <span class="text-red-600 text-sm">{{ $message }}</span>
    @enderror
</div>

<div class="flex justify-end">
    <a href="{{ route('ventas.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg mr-2">
        Cancelar
    </a>
    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
        Guardar
    </button>
</div>

{{-- Script para autocompletar el precio según el artículo seleccionado --}}
<script>
   const precios = {
        @foreach($articulos as $articulo)
            "{{ $articulo->id }}": {{ $articulo->precio ?? 0 }}@if(!$loop->last),@endif
        @endforeach
    };

    const articuloSelect = document.getElementById('articulo_id');
    const precioInput = document.getElementById('precio_venta');

    articuloSelect.addEventListener('change', function() {
        const selectedId = this.value;
        if (precios[selectedId]) {
            precioInput.value = precios[selectedId];
        } else {
            precioInput.value = '';
        }
    });

    // Inicializa el precio al cargar la página si hay artículo seleccionado
    document.addEventListener('DOMContentLoaded', function() {
        const selectedId = articuloSelect.value;
        if (precios[selectedId]) {
            precioInput.value = precios[selectedId];
        }
    });
</script>