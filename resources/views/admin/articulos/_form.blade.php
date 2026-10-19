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
        <x-file-dropzone
            name="img_base64"
            id="img_base64"
            label="Imagen del Artículo"
            accept="image/*"
            placeholder="Haga clic para seleccionar imagen del artículo o arrastre aquí"
            help-text="Formatos permitidos: JPG, PNG, WEBP (Máx. 2MB)"
            :current-image="(isset($articulo) && $articulo->id && !empty($articulo->img_base64)) ? $articulo->imagen_url : null"
            current-image-label="Imagen actual:"
        />
        @error('img_base64')
            <span class="text-rose-500 text-sm mt-1 block font-semibold">{{ $message }}</span>
        @enderror
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
