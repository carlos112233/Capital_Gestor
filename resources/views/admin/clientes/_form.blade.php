<div class="mb-4">
    <label class="block text-gray-700 dark:text-slate-300 font-bold mb-2">Nombre</label>
    <input type="text" name="name" class="w-full bg-white dark:bg-slate-800 border-gray-300 dark:border-slate-700 text-slate-900 dark:text-white rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
        value="{{ old('name', $cliente->name ?? '') }}" required>
    @error('name')
        <span class="text-red-600 dark:text-rose-400 text-sm">{{ $message }}</span>
    @enderror
</div>

<div class="mb-4">
    <label class="block text-gray-700 dark:text-slate-300 font-bold mb-2">Email</label>
    <input type="email" name="email" class="w-full bg-white dark:bg-slate-800 border-gray-300 dark:border-slate-700 text-slate-900 dark:text-white rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
        value="{{ old('email', $cliente->email ?? '') }}" required>
    @error('email')
        <span class="text-red-600 dark:text-rose-400 text-sm">{{ $message }}</span>
    @enderror
</div>

<!-- CAMPO: ROL DEL USUARIO -->
<div class="mb-4">
    <label class="block text-gray-700 dark:text-slate-300 font-bold mb-2">Rol del Usuario</label>
    @php
        $targetUser = $cliente ?? $user ?? null;
        $currentRole = old('role', ($targetUser && $targetUser->exists) ? ($targetUser->roles->first()?->name ?? 'cliente') : 'cliente');
    @endphp
    <select name="role" class="w-full bg-white dark:bg-slate-800 border-gray-300 dark:border-slate-700 text-slate-900 dark:text-white rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 font-medium" required>
        <option value="cliente" {{ $currentRole === 'cliente' ? 'selected' : '' }}>Cliente</option>
        <option value="vendedor" {{ $currentRole === 'vendedor' ? 'selected' : '' }}>Vendedor</option>
        <option value="cocina" {{ $currentRole === 'cocina' ? 'selected' : '' }}>Cocina</option>
        <option value="admin" {{ $currentRole === 'admin' ? 'selected' : '' }}>Administrador (Admin)</option>
    </select>
    @error('role')
        <span class="text-red-600 dark:text-rose-400 text-sm">{{ $message }}</span>
    @enderror
</div>

<!-- CAMPO: TELÉFONO -->
<div class="mb-4">
    <label class="block text-gray-700 dark:text-slate-300 font-bold mb-2">Teléfono</label>
    <input type="text" name="telefono" class="w-full bg-white dark:bg-slate-800 border-gray-300 dark:border-slate-700 text-slate-900 dark:text-white rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
        placeholder="Ej: +593 999 999 999"
        value="{{ old('telefono', $cliente->telefono ?? '') }}">
    @error('telefono')
        <span class="text-red-600 dark:text-rose-400 text-sm">{{ $message }}</span>
    @enderror
</div>

<div class="mb-4" x-data="{ show: false }">
    <label class="block text-gray-700 dark:text-slate-300 font-bold mb-2">Contraseña</label>
    <div class="relative">
        <input :type="show ? 'text' : 'password'" name="password" id="password" class="w-full pr-10 bg-white dark:bg-slate-800 border-gray-300 dark:border-slate-700 text-slate-900 dark:text-white rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500"
            placeholder="{{ isset($cliente) ? 'Dejar en blanco para no cambiar' : 'Mínimo 6 caracteres' }}">
        <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-white focus:outline-none">
            <svg x-show="!show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
            <svg x-show="show" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
        </button>
    </div>
    @error('password')
        <span class="text-red-600 dark:text-rose-400 text-sm">{{ $message }}</span>
    @enderror
</div>

<div class="mb-4">
    <x-file-dropzone
        name="image"
        id="image"
        label="Avatar / Imagen de Perfil"
        accept="image/*"
        placeholder="Haga clic para seleccionar imagen de perfil o arrastre aquí"
        help-text="Formatos permitidos: JPG, PNG, WEBP (Máx. 2MB)"
        :current-image="(isset($cliente) && $cliente->image) ? $cliente->image_url : null"
        current-image-label="Imagen actual:"
    />
    @error('image')
        <span class="text-rose-500 dark:text-rose-400 text-sm mt-1 block font-semibold">{{ $message }}</span>
    @enderror
</div>

<!-- CHECKBOX ENVIAR ACCESOS POR WHATSAPP -->
<div class="mb-5 bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-950/40 dark:to-teal-950/40 border border-emerald-200 dark:border-emerald-800/40 rounded-xl p-4 shadow-sm">
    <label class="flex items-center gap-3 cursor-pointer">
        <input type="checkbox" name="send_whatsapp_credentials" value="1" 
            class="w-5 h-5 text-emerald-600 border-gray-300 dark:border-slate-700 rounded focus:ring-emerald-500 transition cursor-pointer"
            {{ (!isset($cliente) || !$cliente->id) ? 'checked' : '' }}>
        <div>
            <span class="block text-sm font-bold text-emerald-900 dark:text-emerald-300">
                📲 Enviar accesos por WhatsApp {{ (!isset($cliente) || !$cliente->id) ? 'al registrar' : 'al guardar cambios' }}
            </span>
            <span class="block text-xs text-emerald-700 dark:text-emerald-400 mt-0.5">
                Encola automáticamente las credenciales (Email y Contraseña) al número de WhatsApp del cliente.
            </span>
        </div>
    </label>
</div>

<div class="flex justify-end mt-6">
    <button type="button" x-data x-on:click="$dispatch('close-modal', 'create-cliente'); $dispatch('close-modal', 'edit-cliente-{{ $cliente->id ?? 0 }}')"
        class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold text-sm shadow-sm transition-all duration-200 hover:border-slate-400 focus:outline-none cursor-pointer mr-3">
        Cancelar
    </button>
    <button type="submit" 
        class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-sm shadow-md shadow-indigo-500/25 hover:shadow-lg hover:shadow-indigo-500/35 transition-all duration-200 transform hover:-translate-y-0.5 focus:outline-none cursor-pointer">
        {{ (isset($cliente) && $cliente->id) ? 'Actualizar Cliente' : 'Guardar Cliente' }}
    </button>
</div>