<div class="mb-4">
    <label class="block text-gray-700 font-bold mb-2">Nombre</label>
    <input type="text" name="nombre" class="w-full border-gray-300 rounded-lg shadow-sm"
           value="{{ old('nombre', $cliente->nombre ?? '') }}">
    @error('nombre')
        <span class="text-red-600 text-sm">{{ $message }}</span>
    @enderror
</div>

<div class="mb-4">
    <label>Email</label>
    <input type="email" name="email" class="w-full border-gray-300 rounded-lg shadow-sm"
           value="{{ old('email', $cliente->email ?? '') }}">
</div>

<div class="mb-4">
    <label>Teléfono</label>
    <input type="text" name="telefono" class="w-full border-gray-300 rounded-lg shadow-sm"
           value="{{ old('telefono', $cliente->telefono ?? '') }}">
</div>

<div class="mb-4">
    <label>Dirección</label>
    <textarea name="direccion" class="w-full border-gray-300 rounded-lg shadow-sm">{{ old('direccion', $cliente->direccion ?? '') }}</textarea>
</div>

<div class="flex justify-end">
    <a href="{{ route('admin.clientes.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg mr-2">
        Cancelar
    </a>
    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
        Guardar
    </button>
</div>
