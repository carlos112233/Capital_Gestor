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
    // Estos datos ahora son ligeros porque el controlador solo envía 3 columnas
    const articulos = @json($articulos);
    const tipoPago = document.getElementById('tipo_pago');
    const articuloSelect = document.getElementById('articulo_id');
    const precioInput = document.getElementById('precio_venta');
    
    let selectedArticuloId = "{{ $entrada->articulo_id ?? '' }}";
    const esSaldarFlujo = "{{ $esSaldar }}" == "1";

    function actualizarListaArticulos() {
        const tipo = tipoPago.value;
        articuloSelect.innerHTML = '<option value="" disabled selected>Seleccione un artículo</option>';
        
        // Filtramos en memoria (muy rápido)
        const filtrados = articulos.filter(art => {
            const esSaldarPago = art.nombre.toLowerCase() === 'saldar pago';
            return (tipo === '2') ? esSaldarPago : !esSaldarPago;
        });

        filtrados.forEach(art => {
            const opt = document.createElement('option');
            opt.value = art.id;
            opt.textContent = art.nombre;
            opt.dataset.precio = art.precio;
            
            // Si venimos de "Saldar deuda", seleccionamos automáticamente el artículo correcto
            if (esSaldarFlujo && art.nombre.toLowerCase() === 'saldar pago') {
                opt.selected = true;
            } else if (art.id == selectedArticuloId) {
                opt.selected = true;
            }
            
            articuloSelect.appendChild(opt);
        });
    }

    // Evento al cambiar Tipo de Pago
    tipoPago.addEventListener('change', function() {
        actualizarListaArticulos();
        
        // Si no es el flujo de saldar, ponemos el precio del primer artículo de la lista
        if (!esSaldarFlujo && articuloSelect.options.length > 1) {
            const primerArt = articulos.find(a => a.id == articuloSelect.value);
            if (primerArt) precioInput.value = parseFloat(primerArt.precio).toFixed(2);
        }
    });

    // Evento al cambiar Artículo
    articuloSelect.addEventListener('change', function() {
        const art = articulos.find(a => a.id == this.value);
        if (art && !esSaldarFlujo) {
            precioInput.value = parseFloat(art.precio).toFixed(2);
        }
    });

    // Iniciar al cargar
    document.addEventListener('DOMContentLoaded', actualizarListaArticulos);
</script>