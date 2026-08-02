{{-- resources/views/admin/articulos/_form.blade.php --}}

@if ($errors->any())
    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
        <strong>¡Ups! Hubo algunos problemas con tu articulo.</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="space-y-4">
    <!-- Nombre -->
    <div>
        <x-input-label for="nombre" :value="__('Nombre del Artículo')" />
        <x-text-input id="nombre" class="block mt-1 w-full" type="text" name="nombre" :value="old('nombre', $articulo->nombre ?? '')" required
            autofocus />
    </div>

    <!-- Precio -->
    <div>
        <x-input-label for="precio" :value="__('Precio ($)')" />
        <x-text-input id="precio" class="block mt-1 w-full" type="number" name="precio" :value="old('precio', $articulo->precio ?? '')" required
            step="1" />
    </div>

    <!-- Stock -->
    <div>
        <x-input-label for="stock" :value="__('Stock (Unidades)')" />
        <x-text-input id="stock" class="block mt-1 w-full" type="number" name="stock" :value="old('stock', $articulo->stock ?? '')" required
            step="1" />
    </div>

    <div class="mb-4">
        <label for="img_base64" class="block text-gray-700 font-bold mb-2">Imagen del Artículo</label>
        <div x-data="{ fileName: '' }" class="relative">
            <label class="flex items-center justify-center gap-3 w-full px-4 py-3 rounded-xl border-2 border-dashed border-indigo-300 hover:border-indigo-500 bg-indigo-50/50 hover:bg-indigo-50 text-indigo-700 font-semibold text-sm cursor-pointer transition-all duration-200 shadow-sm">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                <span x-text="fileName ? fileName : 'Haga clic para seleccionar imagen del artículo o arrastre aquí'"></span>
                <input type="file" name="img_base64" id="img_base64" accept="image/*" class="hidden"
                       @change="fileName = $event.target.files[0] ? $event.target.files[0].name : ''">
            </label>
        </div>
        <p class="text-gray-500 text-xs mt-1">Formatos permitidos: JPG, PNG, WEBP. Máximo 2MB.</p>
        @error('img_base64')
            <span class="text-red-500 text-sm">{{ $message }}</span>
        @enderror

        @if(isset($articulo) && $articulo->img_base64)
            <div class="mt-3">
                <p class="text-sm text-gray-600 mb-1">Imagen actual:</p>
                <img src="data:{{ $articulo->imagen_tipo }};base64,{{ $articulo->img_base64 }}" 
                     class="w-32 h-32 object-cover rounded-lg border shadow-md">
            </div>
        @endif
    </div>

    <!-- Descripción -->
    <div>
        <x-input-label for="descripcion" :value="__('Descripción (Opcional)')" />
        <textarea id="descripcion" name="descripcion"
            class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('descripcion', $articulo->descripcion ?? '') }}</textarea>
    </div>
</div>

<div class="flex items-center justify-end mt-6">
    <button type="button" x-data x-on:click="$dispatch('close-modal', 'create-articulo'); $dispatch('close-modal', 'edit-articulo-{{ $articulo->id ?? 0 }}')" 
        class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-sm shadow-sm transition-all duration-200 hover:border-slate-400 focus:outline-none cursor-pointer mr-3">
        Cancelar
    </button>
    <button type="submit" 
        class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-sm shadow-md shadow-indigo-500/25 hover:shadow-lg hover:shadow-indigo-500/35 transition-all duration-200 transform hover:-translate-y-0.5 focus:outline-none cursor-pointer">
        {{ (isset($articulo) && $articulo->id) ? 'Actualizar Artículo' : 'Crear Artículo' }}
    </button>
</div>
