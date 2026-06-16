@php
if (old('pedidos')) {
    $pedidos = old('pedidos');
} elseif (isset($pedido) && isset($pedido->id)) {
    $pedidos = $pedidos ?? [$pedido->toArray()];
} else {
    $pedidos = [ [] ];
}
$userSelected = $userSelected ?? ($pedido->user_id ?? Auth::id());
@endphp

<style>
    .ts-control { border-radius: 0.5rem !important; padding: 0.5rem !important; border: 1px solid #d1d5db !important; }
</style>

<div id="pedidos-container">
@foreach($pedidos as $i => $p)
<div class="pedido-item mb-4 border p-4 rounded-lg bg-white shadow-sm">
    {{-- Artículo --}}
    <div class="mb-2">
        <label class="block font-bold text-gray-700">Artículo</label>
        <select name="pedidos[{{ $i }}][articulo_id]"
                class="articulo-select w-full border-gray-300 rounded-lg"
                required>
            <option value="">Seleccione un artículo...</option>
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
        <input type="number" name="pedidos[{{ $i }}][cantidad]" min="1"
               class="cantidad-input w-full border-gray-300 rounded-lg"
               value="{{ old("pedidos.$i.cantidad", $p['cantidad'] ?? 1) }}" required>
    </div>

    {{-- Costo --}}
    <div class="mb-2">
        <label class="block font-bold text-gray-700">Costo unitario</label>
        @if(Auth::user()->hasRole('admin'))
            <input type="number" name="pedidos[{{ $i }}][costo]"
                   class="costo-input w-full border-gray-300 rounded-lg"
                   value="{{ old("pedidos.$i.costo", $p['costo'] ?? '') }}" required>
        @else
        <input type="number" name="pedidos[{{ $i }}][costo]"
               class="costo-input w-full border-gray-300 rounded-lg bg-gray-50"
               value="{{ old("pedidos.$i.costo", $p['costo'] ?? '') }}" readonly required>
        @endif       
    </div>

    {{-- Descripción --}}
    <div class="mb-2">
        <label class="block font-bold text-gray-700">Descripción</label>
        <textarea name="pedidos[{{ $i }}][descripcion]"
                  class="w-full border-gray-300 rounded-lg">{{ old("pedidos.$i.descripcion", $p['descripcion'] ?? '') }}</textarea>
    </div>

    @if(Auth::user()->hasRole('admin'))
    <div class="mb-2">
        <label class="block font-bold text-gray-700">Usuario</label>
        <select name="pedidos[{{ $i }}][user_id]" class="w-full border-gray-300 rounded-lg" required>
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

    @if(isset($p['id']))
        <input type="hidden" name="pedidos[{{ $i }}][id]" value="{{ $p['id'] }}">
    @endif
</div>
@endforeach
</div>

<div class="flex justify-end mb-4">
    <button type="button" id="agregar-pedido" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">+</button>
</div>

<div class="flex justify-end gap-2">
    <a href="{{ route('pedidos.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">Cancelar</a>
    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">Guardar</button>
</div>

<script>
// 1. Mapa de precios (Tu lógica original)
const precios = {
@foreach($articulos as $articulo)
    "{{ $articulo->id }}": {{ $articulo->precio ?? 0 }},
@endforeach
};

// 2. Función de costo (Tu lógica original)
function actualizarCosto(select) {
    const block = select.closest('.pedido-item');
    const costo = block.querySelector('.costo-input');
    costo.value = precios[select.value] ?? '';
}

// 3. Función para activar el buscador
function activarBuscador(el) {
    if (el.tomselect) return;
    new TomSelect(el, {
        create: false,
        placeholder: "Buscar artículo...",
        onChange: function() { actualizarCosto(el); }
    });
}

// --- VARIABLE PARA EL MOLDE LIMPIO ---
let moldeLimpio = null;
let index = {{ count($pedidos) }};

document.addEventListener("DOMContentLoaded", function() {
    // 4. GUARDAR MOLDE ANTES DE ACTIVAR NADA
    const original = document.querySelector('.pedido-item');
    moldeLimpio = original.cloneNode(true);
    
    // Limpiar el molde para que esté vacío para futuros usos
    moldeLimpio.querySelectorAll('input, textarea').forEach(el => el.value = '');
    const selectMolde = moldeLimpio.querySelector('.articulo-select');
    selectMolde.value = "";

    // 5. Inicializar buscador en las filas que ya existen (Edición o Errores de validación)
    document.querySelectorAll('.articulo-select').forEach(select => {
        activarBuscador(select);
        actualizarCosto(select);
    });
});

// 6. Lógica del botón AGREGAR (+) corregida
document.getElementById('agregar-pedido').addEventListener('click', () => {
    const container = document.getElementById('pedidos-container');
    
    // CLONAMOS EL MOLDE LIMPIO (No el que ya tiene Tom Select)
    const clone = moldeLimpio.cloneNode(true);

    // Cambiar índices [0] -> [1], [2]... (Tu lógica original)
    clone.querySelectorAll('input, textarea, select').forEach(el => {
        if (el.name) {
            el.name = el.name.replace(/\[\d+\]/, `[${index}]`);
        }
         if (el.type === 'number') el.value = el.name.includes('cantidad') ? 1 : '';
        // Limpiar el ID si lo tiene (para que no se duplique en el HTML)
        if(el.id) el.id = "";
    });

    // Quitar el input hidden del ID en caso de que el molde lo tenga
    const hiddenId = clone.querySelector('input[type="hidden"][name*="[id]"]');
    if (hiddenId) hiddenId.remove();

    // Insertar el clon
    container.appendChild(clone);

    // ACTIVAR EL BUSCADOR EN EL NUEVO SELECT
    const nuevoSelect = clone.querySelector('.articulo-select');
    activarBuscador(nuevoSelect);

    index++;
});
</script>