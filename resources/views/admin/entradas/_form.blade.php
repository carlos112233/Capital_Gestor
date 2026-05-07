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
        <option value="" disabled {{ !isset($entrada) && !$esSaldar ? 'selected' : '' }}>Seleccione un tipo</option>
        <option value="1" {{ (isset($entrada) && $entrada->articulo->nombre != 'Saldar pago') ? 'selected' : '' }}>Por artículo</option>
        {{-- Si esSaldar es true, seleccionamos esta opción por defecto --}}
        <option value="2" {{ $esSaldar || (isset($entrada) && $entrada->articulo->nombre == 'Saldar pago') ? 'selected' : '' }}>Saldar adeudo</option>
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
<div class="flex justify-end">
    <a href="{{ route('admin.entradas.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg mr-2">
        Cancelar
    </a>
    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
        Guardar
    </button>
</div>
{{-- BOTONES IGUALES... --}}

<script>
    const articulos = @json($articulos);
    const tipoPago = document.getElementById('tipo_pago');
    const articuloSelect = document.getElementById('articulo_id');
    const precioInput = document.getElementById('precio_venta');
    
    // Si es una edición, tomamos el ID actual, si no, lo dejamos vacío para que JS lo maneje
    let selectedArticuloId = "{{ $entrada->articulo_id ?? '' }}";
    
    // Si venimos de la URL de "Saldar", buscamos el ID del artículo que se llama 'Saldar pago'
    if ("{{ $esSaldar }}" == "1") {
        const artSaldar = articulos.find(a => a.nombre === 'Saldar pago');
        if (artSaldar) selectedArticuloId = artSaldar.id;
    }

    function llenarArticulos() {
        const tipo = tipoPago.value;
        articuloSelect.innerHTML = '<option value="" disabled>Seleccione un artículo</option>';
        let options = [];

        articulos.forEach(art => {
            // Normalizar a minúsculas para evitar errores de escritura
            const nombreArt = art.nombre.toLowerCase();
            if(tipo == '1' && nombreArt != 'saldar pago') {
                options.push(art);
            } else if(tipo == '2' && nombreArt == 'saldar pago') {
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
    }

    tipoPago.addEventListener('change', function() {
        llenarArticulos();
        // Al cambiar manualmente, actualizamos el precio basado en el artículo
        const firstArt = articulos.find(a => a.id == articuloSelect.value);
        if (firstArt && "{{ $esSaldar }}" != "1") { // Solo auto-cambiar precio si no es el flujo de "Saldar"
             precioInput.value = Math.round(firstArt.precio);
        }
    });

    articuloSelect.addEventListener('change', function() {
        const precio = articulos.find(a => a.id == this.value)?.precio ?? 0;
        precioInput.value = Math.round(precio);
    });

    document.addEventListener('DOMContentLoaded', llenarArticulos);
</script>