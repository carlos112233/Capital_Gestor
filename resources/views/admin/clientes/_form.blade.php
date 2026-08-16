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

<div class="mb-4" x-data="{ show: false }">
    <label class="block text-gray-700 font-bold mb-2">Contraseña</label>
    <div class="relative">
        <input :type="show ? 'text' : 'password'" name="password" id="password" class="w-full pr-10 border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
            placeholder="{{ isset($cliente) ? 'Dejar en blanco para no cambiar' : 'Mínimo 6 caracteres' }}">
        <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none">
            <svg x-show="!show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
            <svg x-show="show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
        </button>
    </div>
    @error('password')
        <span class="text-red-600 text-sm">{{ $message }}</span>
    @enderror
</div>

<div class="mb-4">
    <label for="image" class="block text-gray-700 font-bold mb-2">Imagen de Perfil</label>
    <div x-data="{ fileName: '' }" class="relative">
        <label class="flex items-center justify-center gap-3 w-full px-4 py-3 rounded-xl border-2 border-dashed border-indigo-300 hover:border-indigo-500 bg-indigo-50/50 hover:bg-indigo-50 text-indigo-700 font-semibold text-sm cursor-pointer transition-all duration-200 shadow-sm">
            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg>
            <span x-text="fileName ? fileName : 'Haga clic para seleccionar imagen de perfil o arrastre aquí'"></span>
            <input type="file" name="image" id="image" accept="image/*" class="hidden"
                   @change="fileName = $event.target.files[0] ? $event.target.files[0].name : ''">
        </label>
    </div>
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

<!-- CHECKBOX ENVIAR ACCESOS POR WHATSAPP -->
<div class="mb-5 bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200 rounded-xl p-4 shadow-sm">
    <label class="flex items-center gap-3 cursor-pointer">
        <input type="checkbox" name="send_whatsapp_credentials" value="1" 
            class="w-5 h-5 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500 transition cursor-pointer"
            {{ (!isset($cliente) || !$cliente->id) ? 'checked' : '' }}>
        <div>
            <span class="block text-sm font-bold text-emerald-900">
                📲 Enviar accesos por WhatsApp {{ (!isset($cliente) || !$cliente->id) ? 'al registrar' : 'al guardar cambios' }}
            </span>
            <span class="block text-xs text-emerald-700 mt-0.5">
                Encola automáticamente las credenciales (Email y Contraseña) al número de WhatsApp del cliente.
            </span>
        </div>
    </label>
</div>

<div class="flex justify-end mt-6">
    <button type="button" x-data x-on:click="$dispatch('close-modal', 'create-cliente'); $dispatch('close-modal', 'edit-cliente-{{ $cliente->id ?? 0 }}')"
        class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-sm shadow-sm transition-all duration-200 hover:border-slate-400 focus:outline-none cursor-pointer mr-3">
        Cancelar
    </button>
    <button type="submit" 
        class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-sm shadow-md shadow-indigo-500/25 hover:shadow-lg hover:shadow-indigo-500/35 transition-all duration-200 transform hover:-translate-y-0.5 focus:outline-none cursor-pointer">
        {{ (isset($cliente) && $cliente->id) ? 'Actualizar Cliente' : 'Guardar Cliente' }}
    </button>
</div>