{{-- resources/views/admin/articulos/_form.blade.php --}}

@if ($errors->any())
    <div class="mb-4 bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 px-4 py-3 rounded-xl relative" role="alert">
        <strong class="font-bold">¡Ups! Hubo algunos problemas con tu artículo ({{ $appSettings->app_name ?? 'El Bajón' }}).</strong>
        <ul class="mt-1 list-disc list-inside text-sm">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@php
    $defaultRequiereCocina = old('requiere_cocina', isset($articulo) && $articulo->exists ? (int) $articulo->requiere_cocina : 1);
    $defaultCategoriaCocina = old('categoria_cocina', isset($articulo) && $articulo->exists ? ($articulo->categoria_cocina ?? 'otro') : 'otro');
@endphp

<div class="space-y-4" x-data="{
    requiereCocina: {{ $defaultRequiereCocina ? 'true' : 'false' }},
    categoriaCocina: '{{ $defaultCategoriaCocina }}'
}">
    <!-- Nombre -->
    <div>
        <x-input-label for="nombre" :value="__('Nombre del Artículo')" class="dark:text-slate-200" />
        <x-text-input id="nombre" class="block mt-1 w-full dark:bg-slate-800 dark:border-slate-700 dark:text-white" type="text" name="nombre" :value="old('nombre', $articulo->nombre ?? '')" required
            autofocus />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Precio -->
        <div>
            <x-input-label for="precio" :value="__('Precio ($)')" class="dark:text-slate-200" />
            <x-text-input id="precio" class="block mt-1 w-full dark:bg-slate-800 dark:border-slate-700 dark:text-white" type="number" name="precio" :value="old('precio', $articulo->precio ?? '')" required
                step="0.01" />
        </div>

        <!-- Stock -->
        <div>
            <x-input-label for="stock" :value="(isset($articulo) && $articulo->id) ? __('Sumar / Restar Stock (Unidades)') : __('Stock Inicial (Unidades)')" class="dark:text-slate-200" />
            <x-text-input id="stock" class="block mt-1 w-full dark:bg-slate-800 dark:border-slate-700 dark:text-white" type="number" name="stock" :value="old('stock', (isset($articulo) && $articulo->id) ? 0 : ($articulo->stock ?? 0))" required
                step="1" />
        </div>
    </div>

    <!-- Metadatos Estructurados de Cocina y Despacho -->
    <div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50/70 dark:bg-slate-800/60 space-y-3">
        <div class="flex items-center justify-between">
            <div>
                <span class="text-sm font-bold text-slate-800 dark:text-slate-100 block">
                    🧑‍🍳 Despacho y Preparación en Cocina
                </span>
                <span class="text-xs text-slate-500 dark:text-slate-400">
                    Define si este producto notifica a cocina y cómo contabiliza milanesas.
                </span>
            </div>
            <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                <input type="hidden" name="requiere_cocina" value="0">
                <input type="checkbox"
                    name="requiere_cocina"
                    value="1"
                    x-model="requiereCocina"
                    @change="if (!requiereCocina && ['cemita', 'milanesa_pollo', 'milanesa_puerco'].includes(categoriaCocina)) { categoriaCocina = 'bebida'; }"
                    {{ $defaultRequiereCocina ? 'checked' : '' }}
                    class="rounded border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500 w-5 h-5 cursor-pointer">
                <span class="text-xs font-semibold text-slate-700 dark:text-slate-200" x-text="requiereCocina ? 'Sí requiere cocina' : 'No requiere cocina'"></span>
            </label>
        </div>

        <div>
            <x-input-label for="categoria_cocina" :value="__('Categoría de Cocina')" class="dark:text-slate-200 text-xs font-semibold" />
            <select id="categoria_cocina"
                name="categoria_cocina"
                x-model="categoriaCocina"
                @change="if (categoriaCocina === 'bebida') { requiereCocina = false; } else if (['cemita', 'milanesa_pollo', 'milanesa_puerco'].includes(categoriaCocina)) { requiereCocina = true; }"
                class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-100 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                <option value="cemita" @selected($defaultCategoriaCocina === 'cemita')>🥪 Cemita Especial / Combinada (1 Pollo + 1 Puerco)</option>
                <option value="milanesa_pollo" @selected($defaultCategoriaCocina === 'milanesa_pollo')>🍗 Cemita / Platillo de Milanesa de Pollo (1 Pollo)</option>
                <option value="milanesa_puerco" @selected($defaultCategoriaCocina === 'milanesa_puerco')>🥩 Cemita / Platillo de Milanesa de Puerco (1 Puerco)</option>
                <option value="otro" @selected($defaultCategoriaCocina === 'otro')>🍽️ Otro Platillo o Producto General</option>
                <option value="bebida" @selected($defaultCategoriaCocina === 'bebida')>🥤 Bebida / Refresco (Sin despacho a cocina)</option>
            </select>
            @error('categoria_cocina')
                <span class="text-rose-500 text-xs mt-1 block font-semibold">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="mb-4">
        <x-file-dropzone
            name="img_base64"
            id="img_base64_{{ $articulo->id ?? 'new' }}"
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
        <x-input-label for="descripcion" :value="__('Descripción (Opcional)')" class="dark:text-slate-200" />
        <textarea id="descripcion" name="descripcion"
            class="block mt-1 w-full border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('descripcion', $articulo->descripcion ?? '') }}</textarea>
    </div>
</div>

<div class="flex items-center justify-end mt-6">
    <button type="button" x-data x-on:click="$dispatch('close-modal', 'create-articulo'); $dispatch('close-modal', 'edit-articulo-{{ $articulo->id ?? 0 }}')" 
        class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold text-sm shadow-sm transition-all duration-200 hover:border-slate-400 focus:outline-none cursor-pointer mr-3">
        Cancelar
    </button>
    <button type="submit" 
        class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-sm shadow-md shadow-indigo-500/25 hover:shadow-lg hover:shadow-indigo-500/35 transition-all duration-200 transform hover:-translate-y-0.5 focus:outline-none cursor-pointer">
        {{ (isset($articulo) && $articulo->id) ? 'Actualizar Artículo' : 'Crear Artículo' }}
    </button>
</div>
