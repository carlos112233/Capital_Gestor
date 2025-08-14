{{-- resources/views/admin/articulos/_form.blade.php --}}

@if ($errors->any())
    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
        <strong>¡Ups! Hubo algunos problemas con tu entrada.</strong>
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
        <x-text-input id="nombre" class="block mt-1 w-full" type="text" name="nombre" :value="old('nombre', $articulo->nombre ?? '')" required autofocus />
    </div>

    <!-- Precio -->
    <div>
        <x-input-label for="precio" :value="__('Precio ($)')" />
        <x-text-input id="precio" class="block mt-1 w-full" type="number" name="precio" :value="old('precio', $articulo->precio ?? '')" required step="1" />
    </div>

    <!-- Stock -->
    <div>
        <x-input-label for="stock" :value="__('Stock (Unidades)')" />
        <x-text-input id="stock" class="block mt-1 w-full" type="number" name="stock" :value="old('stock', $articulo->stock ?? '')" required step="1" />
    </div>

    <!-- Descripción -->
    <div>
        <x-input-label for="descripcion" :value="__('Descripción (Opcional)')" />
        <textarea id="descripcion" name="descripcion" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('descripcion', $articulo->descripcion ?? '') }}</textarea>
    </div>
</div>

<div class="flex items-center justify-end mt-6">
    <a href="{{ route('admin.articulos.index') }}" class="text-sm text-gray-600 hover:text-gray-900 mr-4">
        Cancelar
    </a>
    <x-primary-button>
        {{ isset($articulo) ? 'Actualizar Artículo' : 'Crear Artículo' }}
    </x-primary-button>
</div>