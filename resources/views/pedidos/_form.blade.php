@php
    // Pedidos existentes: edit o create
    $pedidos = $pedidos ?? (isset($pedido) ? [$pedido->toArray()] : []);
    $userSelected = $userSelected ?? $pedido->user_id ?? Auth::id();
@endphp

<div id="pedidos-container">
    @if(count($pedidos) > 0)
        @foreach($pedidos as $i => $p)
            <div class="pedido-item mb-4 border p-4 rounded-lg">
                {{-- Artículo --}}
                <div class="mb-2">
                    <label class="block text-gray-700 font-bold">Artículo</label>
                    <select class="articulo-select w-full border-gray-300 rounded-lg shadow-sm" name="pedidos[{{ $i }}][articulo_id]" required>
                        <option value="">Seleccione un artículo</option>
                        @foreach ($articulos as $articulo)
                            @if($articulo->nombre != "Saldar pago")
                                <option value="{{ $articulo->id }}"
                                    {{ (old("pedidos.$i.articulo_id", $p['articulo_id'] ?? '') == $articulo->id) ? 'selected' : '' }}>
                                    {{ $articulo->nombre }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                </div>

                {{-- Cantidad --}}
                <div class="mb-2">
                    <label class="block text-gray-700 font-bold">Cantidad</label>
                    <input type="number" name="pedidos[{{ $i }}][cantidad]" min="1"
                        value="{{ old("pedidos.$i.cantidad", $p['cantidad'] ?? 1) }}"
                        class="cantidad-input w-full border-gray-300 rounded-lg shadow-sm" readonly required>
                </div>

                {{-- Costo unitario --}}
                <div class="mb-2">
                    <label class="block text-gray-700 font-bold">Costo unitario</label>
                    <input type="number" name="pedidos[{{ $i }}][costo]" step="1"
                        value="{{ old("pedidos.$i.costo", $p['costo'] ?? $p['precio_venta'] ?? '') }}"
                        class="costo-input w-full border-gray-300 rounded-lg shadow-sm" required>
                </div>

                {{-- Descripción --}}
                <div class="mb-2">
                    <label class="block text-gray-700 font-bold">Descripción</label>
                    <textarea name="pedidos[{{ $i }}][descripcion]"
                        class="w-full border-gray-300 rounded-lg shadow-sm">{{ old("pedidos.$i.descripcion", $p['descripcion'] ?? '') }}</textarea>
                </div>
            </div>
        @endforeach
    @else
        {{-- Bloque inicial para create --}}
        <div class="pedido-item mb-4 border p-4 rounded-lg">
            <div class="mb-2">
                <label class="block text-gray-700 font-bold">Artículo</label>
                <select class="articulo-select w-full border-gray-300 rounded-lg shadow-sm" name="pedidos[0][articulo_id]" required>
                    <option value="">Seleccione un artículo</option>
                    @foreach ($articulos as $articulo)
                        @if($articulo->nombre != "Saldar pago")
                            <option value="{{ $articulo->id }}">{{ $articulo->nombre }}</option>
                        @endif
                    @endforeach
                </select>
            </div>

            <div class="mb-2">
                <label class="block text-gray-700 font-bold">Cantidad</label>
                <input type="number" name="pedidos[0][cantidad]" min="1" value="1" class="cantidad-input w-full border-gray-300 rounded-lg shadow-sm"required>
            </div>

            <div class="mb-2">
                <label class="block text-gray-700 font-bold">Costo unitario</label>
                <input type="number" name="pedidos[0][costo]" step="1" class="costo-input w-full border-gray-300 rounded-lg shadow-sm" readonly required>
            </div>

            <div class="mb-2">
                <label class="block text-gray-700 font-bold">Descripción</label>
                <textarea name="pedidos[0][descripcion]" class="w-full border-gray-300 rounded-lg shadow-sm"></textarea>
            </div>
        </div>
    @endif
</div>

{{-- Botón agregar pedido --}}
<div class="flex justify-end mb-4">
    <button type="button" id="agregar-pedido" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
      +
    </button>
</div>

{{-- Usuario (solo admin) --}}
@if(Auth::user()->hasRole('admin'))
<div class="mb-4">
    <label for="user_id" class="block text-gray-700 font-bold">Usuario</label>
    <select name="user_id" class="w-full border-gray-300 rounded-lg shadow-sm">
        <option value="">Seleccione un usuario</option>
        @foreach($users as $user)
            <option value="{{ $user->id }}" {{ $userSelected == $user->id ? 'selected' : '' }}>
                {{ $user->name }}
            </option>
        @endforeach
    </select>
</div>
@endif

{{-- Botones cancelar / guardar --}}
<div class="flex justify-end gap-2">
    <a href="{{ route('pedidos.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
        Cancelar
    </a>
    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
        Guardar
    </button>
</div>

{{-- Script JS --}}
<script>
const precios = {
    @foreach($articulos as $articulo)
        "{{ $articulo->id }}": {{ $articulo->precio ?? 0 }}@if(!$loop->last),@endif
    @endforeach
};

// Función para actualizar costo por artículo
function actualizarCosto(select) {
    const block = select.closest('.pedido-item');
    const costoInput = block.querySelector('.costo-input');
    const selectedId = select.value;
    costoInput.value = precios[selectedId] ?? '';
}

// Inicializar eventos para todos los selects existentes
document.querySelectorAll('.articulo-select').forEach(select => {
    select.addEventListener('change', function() {
        actualizarCosto(this);
    });
    actualizarCosto(select); // Para edit al cargar
});

// Agregar nuevos bloques dinámicamente
let index = {{ count($pedidos) }};
document.getElementById('agregar-pedido').addEventListener('click', function() {
    const container = document.getElementById('pedidos-container');
    const nuevoPedido = container.children[0].cloneNode(true);

    // Limpiar inputs/selects
    nuevoPedido.querySelectorAll('input, textarea, select').forEach(el => {
        if(el.tagName === 'SELECT') el.selectedIndex = 0;
        else el.value = el.name.includes('cantidad') ? 1 : '';
    });

    // Actualizar nombres con índice único
    nuevoPedido.querySelectorAll('input, textarea, select').forEach(el => {
        el.name = el.name.replace(/\d+/, index);
    });

    // Asignar evento change al nuevo select
    const selectNuevo = nuevoPedido.querySelector('.articulo-select');
    selectNuevo.addEventListener('change', function() {
        actualizarCosto(this);
    });

    container.appendChild(nuevoPedido);
    index++;
});
</script>
