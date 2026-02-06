@php
if (old('pedidos')) {
    // Si hay error de validación
    $pedidos = old('pedidos');
} elseif (isset($pedido) && isset($pedido->id)) {
    // Edición
    $pedidos = $pedidos ?? [$pedido->toArray()];
} else {
    // CREATE → forzar un pedido vacío
    $pedidos = [ [] ];
}

$userSelected = $userSelected ?? ($pedido->user_id ?? Auth::id());
@endphp
<div id="pedidos-container">

@foreach($pedidos as $i => $p)
<div class="pedido-item mb-4 border p-4 rounded-lg">

    {{-- Artículo --}}
    <div class="mb-2">
        <label class="block font-bold text-gray-700">Artículo</label>
        <select name="pedidos[{{ $i }}][articulo_id]"
                class="articulo-select w-full border-gray-300 rounded-lg"
                required>
            <option value="">Seleccione un artículo</option>
            @foreach($articulos as $articulo)
                @if($articulo->nombre !== 'Saldar pago')
                <option value="{{ $articulo->id }}"
                    {{ old("pedidos.$i.articulo_id", $p['articulo_id'] ?? '') == $articulo->id ? 'selected' : '' }}>
                    {{ $articulo->nombre }}
                </option>
                @endif
            @endforeach
        </select>
    </div>

    {{-- Cantidad --}}
    <div class="mb-2">
        <label class="block font-bold text-gray-700">Cantidad</label>
        <input type="number"
               name="pedidos[{{ $i }}][cantidad]"
               min="1"
               class="cantidad-input w-full border-gray-300 rounded-lg"
               value="{{ old("pedidos.$i.cantidad", $p['cantidad'] ?? 1) }}"
               required>
    </div>

    {{-- Costo --}}
    <div class="mb-2">
        <label class="block font-bold text-gray-700">Costo unitario</label>
        <input type="number"
               name="pedidos[{{ $i }}][costo]"
               class="costo-input w-full border-gray-300 rounded-lg"
               value="{{ old("pedidos.$i.costo", $p['costo'] ?? '') }}"
               readonly required>
    </div>

    {{-- Descripción --}}
    <div class="mb-2">
        <label class="block font-bold text-gray-700">Descripción</label>
        <textarea name="pedidos[{{ $i }}][descripcion]"
                  class="w-full border-gray-300 rounded-lg">{{ old("pedidos.$i.descripcion", $p['descripcion'] ?? '') }}</textarea>
    </div>

    {{-- Usuario (solo admin) --}}
    @if(Auth::user()->hasRole('admin'))
    <div class="mb-2">
        <label class="block font-bold text-gray-700">Usuario</label>
        <select name="pedidos[{{ $i }}][user_id]"
                class="w-full border-gray-300 rounded-lg"
                required>
            <option value="">Seleccione un usuario</option>
            @foreach($users as $user)
            <option value="{{ $user->id }}"
                {{ old("pedidos.$i.user_id", $userSelected) == $user->id ? 'selected' : '' }}>
                {{ $user->name }}
            </option>
            @endforeach
        </select>
    </div>
    @endif

    {{-- ID oculto para update --}}
    @if(isset($p['id']))
        <input type="hidden" name="pedidos[{{ $i }}][id]" value="{{ $p['id'] }}">
    @endif

</div>
@endforeach

</div>


{{-- Botón agregar pedido --}}
<div class="flex justify-end mb-4">
    <button type="button"
            id="agregar-pedido"
            class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
        +
    </button>
</div>



<div class="flex justify-end gap-2">
    <a href="{{ route('pedidos.index') }}"
       class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
        Cancelar
    </a>

    <button type="submit"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
        Guardar
    </button>
</div>


{{-- Script JS --}}
<script>
const precios = {
@foreach($articulos as $articulo)
    "{{ $articulo->id }}": {{ $articulo->precio ?? 0 }},
@endforeach
};

function actualizarCosto(select) {
    const block = select.closest('.pedido-item');
    const costo = block.querySelector('.costo-input');
    costo.value = precios[select.value] ?? '';
}

// inicializar selects existentes
document.querySelectorAll('.articulo-select').forEach(select => {
    select.addEventListener('change', () => actualizarCosto(select));
    actualizarCosto(select);
});

let index = {{ count($pedidos) }};

document.getElementById('agregar-pedido').addEventListener('click', () => {

    const container = document.getElementById('pedidos-container');
    const original = container.querySelector('.pedido-item');
    const clone = original.cloneNode(true);

    clone.querySelectorAll('input, textarea, select').forEach(el => {

        // limpiar valores
        if (el.type === 'number') el.value = el.name.includes('cantidad') ? 1 : '';
        else if (el.tagName === 'SELECT') el.selectedIndex = 0;
        else el.value = '';

        // cambiar índice
        el.name = el.name.replace(/\[\d+\]/, `[${index}]`);
    });

    const select = clone.querySelector('.articulo-select');
    select.addEventListener('change', () => actualizarCosto(select));

    container.appendChild(clone);
    index++;
});
</script>
