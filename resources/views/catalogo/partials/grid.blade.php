{{-- resources/views/catalogo/partials/grid.blade.php --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse ($articulos as $articulo)
        @if ($articulo->nombre != 'Saldar pago' && $articulo->stock >=1)
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

                    <form method="POST" action="{{ route('catalogo.vender') }}" class="flex items-center space-x-2">
                        @csrf
                        <input type="hidden" name="articulo_id" value="{{ $articulo->id }}">

                        <!-- Campo de Cantidad -->
                        <x-text-input type="number" name="cantidad" class="w-20 text-center" value="1"
                            min="1" max="{{ $articulo->stock }}" required />

                        <a href="{{ route('ventas.create', ['articulo_id' => $articulo->id]) }}"
                            class="flex-grow inline-flex justify-center items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            @if (Auth::user()->hasRole('admin'))
                                {{ __('Vender') }}
                            @else
                                {{ __('Comprar') }}
                            @endif
                        </a>

                    </form>
                </div>
            </div>
        @endif
    @empty
        <p class="text-center text-gray-500 col-span-full">No hay artículos disponibles para la venta.</p>
    @endforelse
</div>
{{-- <div class="mt-8">
    {{ $articulos->appends(['q' => request('q')])->links() }}
</div> --}}
