<div class="mb-4">
    <label class="block text-gray-700 font-bold mb-2">Nombre</label>
    <input type="text" name="name" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
        value="{{ old('name', $cliente->name ?? '') }}" required>
    @error('name')
        <span class="text-red-600 text-sm">{{ $message }}</span>
    @enderror
</div>

<div class="mb-4">
    <label class="block text-gray-700 font-bold mb-2">Email</label>
    <input type="email" name="email" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
        value="{{ old('email', $cliente->email ?? '') }}" required>
    @error('email')
        <span class="text-red-600 text-sm">{{ $message }}</span>
    @enderror
</div>

<!-- NUEVO CAMPO: TELÉFONO -->
<div class="mb-4">
    <label class="block text-gray-700 font-bold mb-2">Teléfono</label>
    <input type="text" name="telefono" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
        placeholder="Ej: +593 999 999 999"
        value="{{ old('telefono', $cliente->telefono ?? '') }}">
    @error('telefono')
        <span class="text-red-600 text-sm">{{ $message }}</span>
    @enderror
</div>

<div class="mb-4">
    <label class="block text-gray-700 font-bold mb-2">Contraseña</label>
    <input type="password" name="password" id="password" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
        placeholder="{{ isset($cliente) ? 'Dejar en blanco para no cambiar' : 'Mínimo 6 caracteres' }}">
    @error('password')
        <span class="text-red-600 text-sm">{{ $message }}</span>
    @enderror
</div>

<div class="mb-4">
    <label for="image" class="block text-gray-700 font-bold mb-2">Imagen de Perfil</label>
    <input type="file" name="image" id="image" accept="image/*" class="border rounded w-full p-2 bg-white">
    <p class="text-gray-500 text-xs mt-1">Formatos permitidos: JPG, PNG. Máximo 2MB.</p>
    @error('image')
        <span class="text-red-500 text-sm">{{ $message }}</span>
    @enderror

    @if (isset($cliente) && $cliente->image)
        <div class="mt-3">
            <p class="text-sm text-gray-600 mb-1">Imagen actual:</p>
            <img src="data:{{ $cliente->image_tipo }};base64,{{ $cliente->image }}" 
                 class="w-32 h-32 object-cover rounded-lg border shadow-md">
        </div>
    @endif
</div>

<div class="flex justify-end mt-6">
    <a href="{{ route('admin.clientes.index') }}"
        class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg mr-2 transition duration-200">
        Cancelar
    </a>
    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg shadow-lg transition duration-200">
        {{ isset($cliente) ? 'Actualizar Cliente' : 'Guardar Cliente' }}
    </button>
</div>