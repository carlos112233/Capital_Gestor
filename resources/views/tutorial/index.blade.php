<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center gap-2">
            <span>📚</span> {{ __('Manual de Usuario y Tutoriales') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-slate-200">
                <div class="p-8 text-gray-900">
                    <div class="text-center max-w-2xl mx-auto mb-10">
                        <h3 class="text-3xl font-extrabold text-slate-800 tracking-tight">Aprende a usar El Bajón</h3>
                        <p class="mt-4 text-slate-500 text-lg">Selecciona cualquier apartado de la aplicación para iniciar un recorrido guiado e interactivo que te enseñará paso a paso cómo utilizarlo.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        
                        <!-- Tarjeta: Dashboard Cliente -->
                        <div class="bg-slate-50 rounded-xl border border-slate-200 p-6 flex flex-col justify-between hover:shadow-lg transition-shadow duration-300">
                            <div>
                                <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center text-2xl mb-4">🏠</div>
                                <h4 class="text-xl font-bold text-slate-800 mb-2">Mi Resumen (Dashboard)</h4>
                                <p class="text-sm text-slate-500 mb-6">Aprende a leer tu estado de cuenta, revisar tu saldo pendiente y subir tus comprobantes de pago.</p>
                            </div>
                            <a href="{{ route('dashboard') }}?tutorial=true" class="w-full text-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg shadow-sm transition-colors">
                                Iniciar Recorrido
                            </a>
                        </div>

                        <!-- Tarjeta: Pedidos -->
                        <div class="bg-slate-50 rounded-xl border border-slate-200 p-6 flex flex-col justify-between hover:shadow-lg transition-shadow duration-300">
                            <div>
                                <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-lg flex items-center justify-center text-2xl mb-4">📋</div>
                                <h4 class="text-xl font-bold text-slate-800 mb-2">Mis Pedidos</h4>
                                <p class="text-sm text-slate-500 mb-6">Descubre cómo consultar el estado de tus pedidos y solicitar productos.</p>
                            </div>
                            <a href="{{ route('pedidos.index') }}?tutorial=true" class="w-full text-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg shadow-sm transition-colors">
                                Iniciar Recorrido
                            </a>
                        </div>

                        <!-- Tarjeta: Catálogo -->
                        <div class="bg-slate-50 rounded-xl border border-slate-200 p-6 flex flex-col justify-between hover:shadow-lg transition-shadow duration-300">
                            <div>
                                <div class="w-12 h-12 bg-pink-100 text-pink-600 rounded-lg flex items-center justify-center text-2xl mb-4">🛍️</div>
                                <h4 class="text-xl font-bold text-slate-800 mb-2">Catálogo de Productos</h4>
                                <p class="text-sm text-slate-500 mb-6">Conoce cómo navegar por los productos disponibles, ver existencias y armar tu carrito.</p>
                            </div>
                            <a href="{{ route('catalogo.index') }}?tutorial=true" class="w-full text-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg shadow-sm transition-colors">
                                Iniciar Recorrido
                            </a>
                        </div>

                        <!-- Tarjeta: Compras -->
                        <div class="bg-slate-50 rounded-xl border border-slate-200 p-6 flex flex-col justify-between hover:shadow-lg transition-shadow duration-300">
                            <div>
                                <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center text-2xl mb-4">🛒</div>
                                <h4 class="text-xl font-bold text-slate-800 mb-2">Mis Compras</h4>
                                <p class="text-sm text-slate-500 mb-6">Revisa el historial de todo lo que has adquirido y el detalle de tus tickets de compra.</p>
                            </div>
                            <a href="{{ route('ventas.index') }}?tutorial=true" class="w-full text-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg shadow-sm transition-colors">
                                Iniciar Recorrido
                            </a>
                        </div>

                        <!-- Tarjeta: Perfil -->
                        <div class="bg-slate-50 rounded-xl border border-slate-200 p-6 flex flex-col justify-between hover:shadow-lg transition-shadow duration-300">
                            <div>
                                <div class="w-12 h-12 bg-yellow-100 text-yellow-600 rounded-lg flex items-center justify-center text-2xl mb-4">👤</div>
                                <h4 class="text-xl font-bold text-slate-800 mb-2">Mi Perfil</h4>
                                <p class="text-sm text-slate-500 mb-6">Aprende a actualizar tu foto, cambiar tu contraseña y modificar tus datos personales.</p>
                            </div>
                            <a href="{{ route('profile.edit') }}?tutorial=true" class="w-full text-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg shadow-sm transition-colors">
                                Iniciar Recorrido
                            </a>
                        </div>

                        <!-- Tarjeta: Quejas -->
                        <div class="bg-slate-50 rounded-xl border border-slate-200 p-6 flex flex-col justify-between hover:shadow-lg transition-shadow duration-300">
                            <div>
                                <div class="w-12 h-12 bg-red-100 text-red-600 rounded-lg flex items-center justify-center text-2xl mb-4">💬</div>
                                <h4 class="text-xl font-bold text-slate-800 mb-2">Buzón de Sugerencias</h4>
                                <p class="text-sm text-slate-500 mb-6">Si tienes alguna queja o sugerencia, aquí te mostramos cómo hacerla llegar al equipo.</p>
                            </div>
                            <a href="{{ route('feedback.index') }}?tutorial=true" class="w-full text-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg shadow-sm transition-colors">
                                Iniciar Recorrido
                            </a>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
