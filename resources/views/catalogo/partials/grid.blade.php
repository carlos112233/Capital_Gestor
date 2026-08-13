{{-- resources/views/catalogo/partials/grid.blade.php --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse ($articulos as $articulo)
        @if ($articulo->stock >=1)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg flex flex-col">
                <div class="p-6 text-gray-900 flex-grow">
                    <h3 class="text-lg font-bold">{{ $articulo->nombre }}</h3>
                    <p class="mt-2 text-gray-600">{{ $articulo->descripcion }}</p>
                </div>
                @if ($articulo->img_base64)
                    <div class="p-6 bg-gray-50 text-center border-t border-gray-200 flex justify-center">
                        <div class="w-40 h-40 overflow-hidden rounded-full"> <!-- Contenedor cuadrado -->
                            <img class="w-full h-full object-cover"
                                src="data:{{ $articulo->imagen_tipo }};base64,{{ $articulo->img_base64 }}"
                                alt="Imagen del artículo">
                        </div>
                    </div>
                @endif
                <div class="p-6 bg-gray-50 border-t border-gray-200">
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-xl font-semibold">$ {{ number_format($articulo->precio, 2) }}
                            MXN</span>
                        <span class="text-sm text-gray-500">Stock: {{ $articulo->stock }}</span>
                    </div>

                    <div class="flex items-center space-x-2">
                        @if (Auth::user()->hasRole('admin'))
                            <button type="button"
                                onclick="openVentaModal({{ $articulo->id }}, '{{ addslashes($articulo->nombre) }}', {{ $articulo->precio }}, {{ $articulo->stock }})"
                                class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-sm shadow-md shadow-indigo-500/25 hover:shadow-lg hover:shadow-indigo-500/35 transition-all duration-200 transform hover:-translate-y-0.5 focus:outline-none cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                </svg>
                                {{ __('Vender') }}
                            </button>
                        @else
                            <button type="button"
                                x-data
                                @click="$dispatch('add-to-cart', { id: {{ $articulo->id }}, nombre: '{{ addslashes($articulo->nombre) }}', precio: {{ $articulo->precio }}, cantidad: 1 })"
                                class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-sm shadow-md shadow-emerald-500/25 hover:shadow-lg hover:shadow-emerald-500/35 transition-all duration-200 transform hover:-translate-y-0.5 focus:outline-none cursor-pointer">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                {{ __('Agregar al Carrito') }}
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    @empty
        <p class="text-center text-gray-600 col-span-full text-xl font-semibold">No hay artículos disponibles para la venta.</p>
    @endforelse
</div>
{{-- <div class="mt-8">
    {{ $articulos->appends(['q' => request('q')])->links() }}
</div> --}}
