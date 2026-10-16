<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight flex items-center gap-2">
            <span>📚</span> {{ __('Manual de Usuario y Tutoriales') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <div class="bg-white dark:bg-[#161f32] overflow-hidden shadow-sm sm:rounded-2xl border border-slate-200 dark:border-slate-700">
                <div class="p-8 text-gray-900 dark:text-slate-100">
                    <div class="text-center max-w-2xl mx-auto mb-10">
                        <h3 class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">Aprende a usar {{ $appSettings->app_name ?? 'El Bajón' }}</h3>
                        <p class="mt-4 text-slate-500 dark:text-slate-400 text-lg">Selecciona cualquier apartado de la aplicación para iniciar un recorrido guiado e interactivo que te enseñará paso a paso cómo utilizarlo.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        
                        <!-- Tarjeta: Dashboard Cliente -->
                        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 flex flex-col justify-between hover:shadow-lg dark:hover:border-slate-600 transition-all duration-300">
                            <div>
                                <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 rounded-xl flex items-center justify-center text-2xl mb-4 border border-indigo-200/50 dark:border-indigo-800/40">🏠</div>
                                <h4 class="text-base font-bold text-slate-900 dark:text-white mb-2">Mi Resumen (Dashboard)</h4>
                                <p class="text-xs text-slate-600 dark:text-slate-300 mb-6">Aprende a leer tu estado de cuenta, revisar tu saldo pendiente y subir tus comprobantes de pago.</p>
                            </div>
                            <a href="{{ route('dashboard') }}?tutorial=true" class="w-full text-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg shadow-sm transition-colors text-sm">
                                Iniciar Recorrido
                            </a>
                        </div>

                        <!-- Tarjeta: Pedidos -->
                        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 flex flex-col justify-between hover:shadow-lg dark:hover:border-slate-600 transition-all duration-300">
                            <div>
                                <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 rounded-xl flex items-center justify-center text-2xl mb-4 border border-emerald-200/50 dark:border-emerald-800/40">📋</div>
                                <h4 class="text-base font-bold text-slate-900 dark:text-white mb-2">Mis Pedidos</h4>
                                <p class="text-xs text-slate-600 dark:text-slate-300 mb-6">Descubre cómo consultar el estado de tus pedidos y solicitar productos.</p>
                            </div>
                            <a href="{{ route('pedidos.index') }}?tutorial=true" class="w-full text-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg shadow-sm transition-colors text-sm">
                                Iniciar Recorrido
                            </a>
                        </div>

                        <!-- Tarjeta: Catálogo -->
                        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 flex flex-col justify-between hover:shadow-lg dark:hover:border-slate-600 transition-all duration-300">
                            <div>
                                <div class="w-12 h-12 bg-pink-100 dark:bg-pink-950/60 text-pink-600 dark:text-pink-400 rounded-xl flex items-center justify-center text-2xl mb-4 border border-pink-200/50 dark:border-pink-800/40">🛍️</div>
                                <h4 class="text-base font-bold text-slate-900 dark:text-white mb-2">Catálogo de Productos</h4>
                                <p class="text-xs text-slate-600 dark:text-slate-300 mb-6">Conoce cómo navegar por los productos disponibles, ver existencias y armar tu carrito.</p>
                            </div>
                            <a href="{{ route('catalogo.index') }}?tutorial=true" class="w-full text-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg shadow-sm transition-colors text-sm">
                                Iniciar Recorrido
                            </a>
                        </div>

                        <!-- Tarjeta: Compras -->
                        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 flex flex-col justify-between hover:shadow-lg dark:hover:border-slate-600 transition-all duration-300">
                            <div>
                                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 rounded-xl flex items-center justify-center text-2xl mb-4 border border-blue-200/50 dark:border-blue-800/40">🛒</div>
                                <h4 class="text-base font-bold text-slate-900 dark:text-white mb-2">Mis Compras</h4>
                                <p class="text-xs text-slate-600 dark:text-slate-300 mb-6">Revisa el historial de todo lo que has adquirido y el detalle de tus tickets de compra.</p>
                            </div>
                            <a href="{{ route('ventas.index') }}?tutorial=true" class="w-full text-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg shadow-sm transition-colors text-sm">
                                Iniciar Recorrido
                            </a>
                        </div>

                        <!-- Tarjeta: Perfil -->
                        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 flex flex-col justify-between hover:shadow-lg dark:hover:border-slate-600 transition-all duration-300">
                            <div>
                                <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-950/60 text-yellow-600 dark:text-yellow-400 rounded-xl flex items-center justify-center text-2xl mb-4 border border-yellow-200/50 dark:border-yellow-800/40">👤</div>
                                <h4 class="text-base font-bold text-slate-900 dark:text-white mb-2">Mi Perfil</h4>
                                <p class="text-xs text-slate-600 dark:text-slate-300 mb-6">Aprende a actualizar tu foto, cambiar tu contraseña y modificar tus datos personales.</p>
                            </div>
                            <a href="{{ route('profile.edit') }}?tutorial=true" class="w-full text-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg shadow-sm transition-colors text-sm">
                                Iniciar Recorrido
                            </a>
                        </div>

                        <!-- Tarjeta: Quejas -->
                        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 flex flex-col justify-between hover:shadow-lg dark:hover:border-slate-600 transition-all duration-300">
                            <div>
                                <div class="w-12 h-12 bg-red-100 dark:bg-red-950/60 text-red-600 dark:text-red-400 rounded-xl flex items-center justify-center text-2xl mb-4 border border-red-200/50 dark:border-red-800/40">💬</div>
                                <h4 class="text-base font-bold text-slate-900 dark:text-white mb-2">Buzón de Sugerencias</h4>
                                <p class="text-xs text-slate-600 dark:text-slate-300 mb-6">Si tienes alguna queja o sugerencia, aquí te mostramos cómo hacerla llegar al equipo.</p>
                            </div>
                            <a href="{{ route('feedback.index') }}?tutorial=true" class="w-full text-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg shadow-sm transition-colors text-sm">
                                Iniciar Recorrido
                            </a>
                        </div>

                        <!-- Tarjeta: Carrito de Compras -->
                        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 flex flex-col justify-between hover:shadow-lg dark:hover:border-slate-600 transition-all duration-300">
                            <div>
                                <div class="w-12 h-12 bg-orange-100 dark:bg-orange-950/60 text-orange-600 dark:text-orange-400 rounded-xl flex items-center justify-center text-2xl mb-4 border border-orange-200/50 dark:border-orange-800/40">🛒</div>
                                <h4 class="text-base font-bold text-slate-900 dark:text-white mb-2">Carrito de Compras</h4>
                                <p class="text-xs text-slate-600 dark:text-slate-300 mb-6">Descubre cómo usar tu carrito para revisar los productos que estás a punto de solicitar y finalizar la compra.</p>
                            </div>
                            <a href="{{ route('dashboard') }}?tutorial=carrito" class="w-full text-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg shadow-sm transition-colors text-sm">
                                Iniciar Recorrido
                            </a>
                        </div>

                        <!-- Tarjeta: Notificaciones -->
                        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 flex flex-col justify-between hover:shadow-lg dark:hover:border-slate-600 transition-all duration-300">
                            <div>
                                <div class="w-12 h-12 bg-cyan-100 dark:bg-cyan-950/60 text-cyan-600 dark:text-cyan-400 rounded-xl flex items-center justify-center text-2xl mb-4 border border-cyan-200/50 dark:border-cyan-800/40">🔔</div>
                                <h4 class="text-base font-bold text-slate-900 dark:text-white mb-2">Notificaciones</h4>
                                <p class="text-xs text-slate-600 dark:text-slate-300 mb-6">Aprende a revisar las alertas del sistema para estar siempre al día con tus pagos y novedades.</p>
                            </div>
                            <a href="{{ route('dashboard') }}?tutorial=notificaciones" class="w-full text-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg shadow-sm transition-colors text-sm">
                                Iniciar Recorrido
                            </a>
                        </div>

                        <!-- Tarjeta: Instalar App -->
                        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 flex flex-col justify-between hover:shadow-lg dark:hover:border-slate-600 transition-all duration-300">
                            <div>
                                <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 rounded-xl flex items-center justify-center text-2xl mb-4 border border-emerald-200/50 dark:border-emerald-800/40">📱</div>
                                <h4 class="text-base font-bold text-slate-900 dark:text-white mb-2">Instalar Aplicación</h4>
                                <p class="text-xs text-slate-600 dark:text-slate-300 mb-6">Descubre cómo instalar la plataforma en tu dispositivo móvil o computadora para acceso rápido.</p>
                            </div>
                            <a href="{{ route('dashboard') }}?tutorial=instalar" class="w-full text-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg shadow-sm transition-colors text-sm">
                                Iniciar Recorrido
                            </a>
                        </div>

                        @if(Auth::user()->hasRole('vendedor') || Auth::user()->hasRole('admin'))
                        <!-- Tarjeta: Panel de Vendedor -->
                        <div class="bg-white dark:bg-slate-800 rounded-xl border border-blue-200 dark:border-blue-800/50 p-6 flex flex-col justify-between hover:shadow-lg dark:hover:border-blue-700 transition-all duration-300">
                            <div>
                                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 rounded-xl flex items-center justify-center text-2xl mb-4 border border-blue-200/50 dark:border-blue-800/40">💼</div>
                                <h4 class="text-base font-bold text-slate-900 dark:text-white mb-2">Panel de Vendedor</h4>
                                <p class="text-xs text-slate-600 dark:text-slate-300 mb-6">Conoce tus 4 métricas clave (Total Vendido, Ganancia Neta 80%, Pagos Recibidos y Saldo por Cobrar) y tus tablas de historial.</p>
                            </div>
                            <a href="{{ route('dashboardVendedor') }}?tutorial=true" class="w-full text-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg shadow-sm transition-colors text-sm">
                                Iniciar Recorrido
                            </a>
                        </div>
                        @endif

                        @if(Auth::user()->hasRole('admin'))
                        <!-- Tarjeta: Gestión de Usuarios -->
                        <div class="bg-white dark:bg-slate-800 rounded-xl border border-purple-200 dark:border-purple-800/50 p-6 flex flex-col justify-between hover:shadow-lg dark:hover:border-purple-700 transition-all duration-300">
                            <div>
                                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 rounded-xl flex items-center justify-center text-2xl mb-4 border border-purple-200/50 dark:border-purple-800/40">👥</div>
                                <h4 class="text-base font-bold text-slate-900 dark:text-white mb-2">Gestión de Usuarios</h4>
                                <p class="text-xs text-slate-600 dark:text-slate-300 mb-6">Aprende a registrar usuarios, asignar cualquiera de los 4 roles oficiales y enviar credenciales por WhatsApp.</p>
                            </div>
                            <a href="{{ route('admin.clientes.index') }}?tutorial=true" class="w-full text-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg shadow-sm transition-colors text-sm">
                                Iniciar Recorrido
                            </a>
                        </div>

                        <!-- Tarjeta: Nómina y Liquidaciones -->
                        <div class="bg-white dark:bg-slate-800 rounded-xl border border-amber-200 dark:border-amber-800/50 p-6 flex flex-col justify-between hover:shadow-lg dark:hover:border-amber-700 transition-all duration-300">
                            <div>
                                <div class="w-12 h-12 bg-amber-100 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 rounded-xl flex items-center justify-center text-2xl mb-4 border border-amber-200/50 dark:border-amber-800/40">💵</div>
                                <h4 class="text-base font-bold text-slate-900 dark:text-white mb-2">Nómina y Liquidaciones</h4>
                                <p class="text-xs text-slate-600 dark:text-slate-300 mb-6">Domina el cálculo de retención del 20%, el ajuste de porcentajes, la dispersión de pagos y la bitácora auditable.</p>
                            </div>
                            <a href="{{ route('admin.pagos-vendedores.index') }}?tutorial=true" class="w-full text-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg shadow-sm transition-colors text-sm">
                                Iniciar Recorrido
                            </a>
                        </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
