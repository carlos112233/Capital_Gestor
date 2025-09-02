{{-- resources/views/pedidos/_form.blade.php --}}

<div class="mb-4">
    <label for="articulo_id" class="block text-gray-700 font-bold mb-2">Artículo</label>
    <select name="articulo_id" id="articulo_id" class="w-full border-gray-300 rounded-lg shadow-sm">
        <option value="">Seleccione un artículo</option>
        @foreach ($articulos as $articulo)
            @if($articulo->nombre !="Saldar pago")
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
    <label for="costo" class="block text-gray-700 font-bold mb-2">Costo</label>
    <input type="number" step="1" name="costo" id="costo"
        class="w-full border-gray-300 rounded-lg shadow-sm"
        value="{{ old('costo', $pedido->costo ?? ($articuloId ? $articulos->find($articuloId)->precio : '')) }}">
    @error('costo')
        <span class="text-red-600 text-sm">{{ $message }}</span>
    @enderror
</div>

<div class="mb-4">
    <label for="descripcion" class="block text-gray-700 font-bold mb-2">Descripción</label>
    <textarea name="descripcion" id="descripcion"
        class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('descripcion', $pedido->descripcion ?? '') }}</textarea>
    @error('descripcion')
        <span class="text-red-600 text-sm">{{ $message }}</span>
    @enderror
</div>

@if(Auth::user()->hasRole('admin'))
<div class="mb-4">
    <label for="user_id" class="block text-gray-700 font-bold mb-2">Usuario</label>
    <select name="user_id" id="user_id" class="w-full border-gray-300 rounded-lg shadow-sm">
        <option value="">Seleccione un usuario</option>
        @foreach($users as $user)
            <option value="{{ $user->id }}" {{ old('user_id', $pedido->user_id ?? '') == $user->id ? 'selected' : '' }}>
                {{ $user->name }}
            </option>
        @endforeach
    </select>
    @error('user_id')
        <span class="text-red-600 text-sm">{{ $message }}</span>
    @enderror
</div>
@endif

<div class="flex justify-end">
    <a href="{{ route('pedidos.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg mr-2">
        Cancelar
    </a>
    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
        Guardar
    </button>
</div>

<script>
    const precios = {
        @foreach($articulos as $articulo)
            "{{ $articulo->id }}": {{ $articulo->precio ?? 0 }}@if(!$loop->last),@endif
        @endforeach
    };

    const articuloSelect = document.getElementById('articulo_id');
    const precioInput = document.getElementById('costo');

    articuloSelect.addEventListener('change', function() {
        const selectedId = this.value;
        precioInput.value = precios[selectedId] ?? '';
    });

    document.addEventListener('DOMContentLoaded', function() {
        const selectedId = articuloSelect.value;
        if (precios[selectedId]) {
            precioInput.value = precios[selectedId];
        }
    });
</script>
