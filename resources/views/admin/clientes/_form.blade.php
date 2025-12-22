<div class="mb-4">
    <label class="block text-gray-700 font-bold mb-2">Nombre</label>
    <input type="text" name="name" class="w-full border-gray-300 rounded-lg shadow-sm"
           value="{{ old('name', $cliente->name ?? '') }}">
    @error('name')
        <span class="text-red-600 text-sm">{{ $message }}</span>
    @enderror
</div>

<div class="mb-4">
    <label>Email</label>
    <input type="email" name="email" class="w-full border-gray-300 rounded-lg shadow-sm"
           value="{{ old('email', $cliente->email ?? '') }}">
    @error('email')
        <span class="text-red-600 text-sm">{{ $message }}</span>
    @enderror
</div>



<div class="mb-4">
    <label>Contraseña</label>
    <input type="password" name="password" id="password" class="w-full border-gray-300 rounded-lg shadow-sm" value="{{ '' }}">
    @error('password')
        <span class="text-red-600 text-sm">{{ $message }}</span>
    @enderror
</div>

<div class="flex justify-end">
    <a href="{{ route('admin.clientes.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg mr-2">
        Cancelar
    </a>
    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
        Guardar
    </button>
</div>
