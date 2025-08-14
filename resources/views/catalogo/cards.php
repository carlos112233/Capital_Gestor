{{-- Reemplaza el contenido de resources/views/catalogo/index.blade.php con esto --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Catálogo de Artículos') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Mensajes -->
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded" role="alert">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded" role="alert">{{ session('error') }}</div>
            @endif
             @if ($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded" role="alert">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif


            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse ($articulos as $articulo)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg flex flex-col">
                        <div class="p-6 text-gray-900 flex-grow">
                            <h3 class="text-lg font-bold">{{ $articulo->nombre }}</h3>
                            <p class="mt-2 text-gray-600">{{ $articulo->descripcion }}</p>
                        </div>
                        <div class="p-6 bg-gray-50 border-t border-gray-200">
                            <div class="flex justify-between items-center mb-4">
                                <span class="text-xl font-semibold">$ {{ number_format($articulo->precio, 2) }} MXN</span>
                                <span class="text-sm text-gray-500">Stock: {{ $articulo->stock }}</span>
                            </div>
                            
                            <form method="POST" action="{{ route('catalogo.vender') }}" class="flex items-center space-x-2">
                                @csrf
                                <input type="hidden" name="articulo_id" value="{{ $articulo->id }}">
                                
                                <!-- Campo de Cantidad -->
                                <x-text-input type="number" name="cantidad" class="w-20 text-center" value="1" min="1" max="{{ $articulo->stock }}" required />
                                
                                <x-primary-button class="flex-grow justify-center">
                                    {{ __('Vender') }}
                                </x-primary-button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-gray-500 col-span-full">No hay artículos disponibles para la venta.</p>
                @endforelse
            </div>

            <div class="mt-8">
                {{ $articulos->links() }}
            </div>
        </div>
    </div>
</x-app-layout>