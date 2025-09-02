{{-- resources/views/entradas/_form.blade.php --}}

<div class="mb-4">
    <label for="tipo_pago" class="block text-gray-700 font-bold mb-2">Tipo de Pago</label>
    <select name="tipo_pago" id="tipo_pago" class="w-full border-gray-300 rounded-lg shadow-sm">
        <option value="" disabled {{ !isset($entrada) ? 'selected' : '' }}>Seleccione un tipo</option>
        <option value="1" {{ isset($entrada) && $entrada->articulo->nombre != 'Saldar pago' ? 'selected' : '' }}>Por artículo</option>
        <option value="2" {{ isset($entrada) && $entrada->articulo->nombre == 'Saldar pago' ? 'selected' : '' }}>Saldar adeudo</option>
    </select>
</div>

<div class="mb-4">
    <label for="articulo_id" class="block text-gray-700 font-bold mb-2">Artículo</label>
    <select name="articulo_id" id="articulo_id" class="w-full border-gray-300 rounded-lg shadow-sm">
        <option value="" disabled>Seleccione un artículo</option>
         @foreach($articulos as $articulo)
                @if($articulo->nombre!='Saldar Pago')
                    <option value="{{ $articulo->id }}" {{ old('cliente_id', $entrada->cliente_id ?? '') == $articulo->id ? 'selected' : '' }}>
                        {{ $articulo->nombre }}
                    </option>
                @endif
        @endforeach
        {{-- Opciones se llenarán por JS --}}
    </select>
    @error('articulo_id')
        <span class="text-red-600 text-sm">{{ $message }}</span>
    @enderror
</div>

<div class="mb-4">
    <label for="precio_venta" class="block text-gray-700 font-bold mb-2">Precio de Venta</label>
    <input type="number" step="1" name="precio_venta" id="precio_venta"
           class="w-full border-gray-300 rounded-lg shadow-sm"
           value="{{  isset($entrada) && $entrada->precio_venta != null ? number_format($entrada->precio_venta ?? 0, 0, '.', '') : '' }}">
    @error('precio_venta')
        <span class="text-red-600 text-sm">{{ $message }}</span>
    @enderror
</div>
 @if (Auth::user()->hasRole('admin'))
<div class="mb-4">
    <label for="cliente_id" class="block text-gray-700 font-bold mb-2">Cliente</label>
    <select name="cliente_id" id="cliente_id" class="w-full border-gray-300 rounded-lg shadow-sm">
        <option value="" disabled {{ old('cliente_id', $entrada->cliente_id ?? '') == '' ? 'selected' : '' }}>Seleccione un cliente</option>
        @foreach($clientes as $cliente)
            <option value="{{ $cliente->id }}" {{ old('cliente_id', $entrada->cliente_id ?? '') == $cliente->id ? 'selected' : '' }}>
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
              class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('descripcion', $entrada->descripcion ?? '') }}</textarea>
    @error('descripcion')
        <span class="text-red-600 text-sm">{{ $message }}</span>
    @enderror
</div>

<div class="flex justify-end">
    <a href="{{ route('admin.entradas.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg mr-2">
        Cancelar
    </a>
    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
        Guardar
    </button>
</div>

<script>
    const articulos = @json($articulos);
    const tipoPago = document.getElementById('tipo_pago');
    const articuloSelect = document.getElementById('articulo_id');
    const precioInput = document.getElementById('precio_venta');
    const selectedArticuloId = "{{ old('articulo_id', $entrada->articulo_id ?? '') }}";

    function llenarArticulos() {
        const tipo = tipoPago.value;
        articuloSelect.innerHTML = '<option value="" disabled>Seleccione un artículo</option>';
        let options = [];

        articulos.forEach(art => {
            if(tipo == '1' && art.nombre != 'Saldar pago') {
                options.push(art);
            } else if(tipo == '2' && art.nombre == 'Saldar pago') {
                options.push(art);
            }
        });

        options.forEach(art => {
            const opt = document.createElement('option');
            opt.value = art.id;
            opt.textContent = art.nombre;
            if(art.id == selectedArticuloId) {
                opt.selected = true;
            }
            articuloSelect.appendChild(opt);
        });

        // Autocompleta precio del primer artículo seleccionado
        const firstSelected = articuloSelect.querySelector('option:checked');
    }

    tipoPago.addEventListener('change', function() {
        llenarArticulos();
    });

    articuloSelect.addEventListener('change', function() {
        const precio = articulos.find(a => a.id == this.value)?.precio ?? 0;
        precioInput.value = Math.round(precio);
    });

    document.addEventListener('DOMContentLoaded', llenarArticulos);
</script>
