@php
    $userNav = \Illuminate\Support\Facades\Auth::user();
    $isAdminNav = $userNav && $userNav->hasRole('admin');
    
    $waPendientes = 0;
    $waEnviados = 0;
    $tiempoTextoWa = 'Hoy';
    $feedbacksPendientes = 0;
    $pedidosPendientes = 0;
    $misPedidosPendientes = 0;
    $misFeedbacksAtendidos = 0;
    $misPagosMes = 0;

    try {
        if ($isAdminNav) {
            $waPendientes = \Illuminate\Support\Facades\DB::table('whatsapp_pending_messages')->where('status', 'pendiente')->count();
            $waEnviados = \Illuminate\Support\Facades\DB::table('whatsapp_pending_messages')->where('status', 'enviado')->count();
            $ultimoWa = \Illuminate\Support\Facades\DB::table('whatsapp_pending_messages')->orderBy('updated_at', 'desc')->first();
            if ($ultimoWa && $ultimoWa->updated_at) {
                $fecha = \Carbon\Carbon::parse($ultimoWa->updated_at);
                $tiempoTextoWa = $fecha->isFuture() ? now()->diffForHumans() : $fecha->diffForHumans();
            }
            $feedbacksPendientes = \App\Models\Feedback::where('estatus', 'enviado')->count();
            $pedidosPendientes = \App\Models\Pedido::whereNull('venta_id')->count();
        } else if ($userNav) {
            $misPedidosPendientes = \App\Models\Pedido::where('user_id', $userNav->id)->whereNull('venta_id')->count();
            $misFeedbacksAtendidos = \App\Models\Feedback::where('user_id', $userNav->id)->whereIn('estatus', ['leyendo', 'leido'])->count();
            $misPagosMes = \App\Models\Entrada::where(function ($q) use ($userNav) {
                $q->where('user_id', $userNav->id)->orWhere('cliente_id', $userNav->id);
            })->where('created_at', '>=', now()->startOfMonth())->count();
        }
    } catch (\Throwable $e) {
        // En caso de que no existan tablas o error de BD
    }
@endphp
<header class="sticky top-0 z-30 bg-white/90 backdrop-blur-md border-b border-slate-200/80 shadow-xs transition-all duration-300">
    <div class="px-4 sm:px-6 lg:px-8 py-3.5 flex items-center justify-between">
        
        <!-- Izquierda: Botón Hamburguesa & Marca Móvil -->
        <div class="flex items-center gap-3">
            <!-- Botón Toggle Sidebar (Desktop) -->
            <button @click="sidebarOpen = !sidebarOpen" 
                    class="hidden lg:inline-flex p-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 focus:outline-none transition-colors"
                    title="Alternar Menú">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <!-- Botón Toggle Sidebar (Mobile) -->
            <button @click="mobileOpen = !mobileOpen" 
                    class="lg:hidden p-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <!-- Título de la Plataforma o Módulo Actual -->
            <div class="flex flex-col">
                <h1 class="text-lg font-bold text-slate-800 tracking-tight leading-none">
                    PANEL DE CONTROL ADMINISTRATIVO
                </h1>
            </div>
        </div>

        <!-- Derecha: Botón PWA, Notificaciones & Menú del Usuario -->
        <div class="flex items-center gap-2 sm:gap-4">
            
            <!-- Botón PWA Install -->
            <button id="pwa-install-btn" style="display: none;"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-sm transition-all duration-200 cursor-pointer"
                title="Instalar App El bajon">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8l-8 8-8-8"/>
                </svg>
                <span class="hidden sm:inline">Instalar App</span>
            </button>
            
            <!-- Icono de Alertas / Notificaciones Interactivo -->
            <div class="relative" x-data="{ notifOpen: false }">
                <button @click="notifOpen = !notifOpen" @click.away="notifOpen = false"
                    class="relative p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors focus:outline-none cursor-pointer"
                    title="Notificaciones">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 01-6 0v-1m6 0H9" />
                    </svg>
                    <span class="absolute top-1.5 right-1.5 w-2.5 h-2.5 rounded-full bg-emerald-500 ring-2 ring-white animate-pulse"></span>
                </button>

                <!-- Menú Desplegable de Notificaciones -->
                <div x-show="notifOpen" x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-2xl shadow-xl border border-slate-200/80 py-2 z-50 overflow-hidden"
                    style="display: none;">
                    
                    <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> 
                            {{ $isAdminNav ? 'Notificaciones del Sistema' : 'Tus Notificaciones' }}
                        </h4>
                        @php
                            $badgeCount = $isAdminNav 
                                ? (($waPendientes > 0 ? 1 : 0) + ($feedbacksPendientes > 0 ? 1 : 0) + ($pedidosPendientes > 0 ? 1 : 0))
                                : (($misPedidosPendientes > 0 ? 1 : 0) + ($misFeedbacksAtendidos > 0 ? 1 : 0) + ($misPagosMes > 0 ? 1 : 0));
                        @endphp
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600 border border-indigo-100">
                            {{ $badgeCount > 0 ? $badgeCount . ' Nuevas' : 'Al día' }}
                        </span>
                    </div>

                    <div class="max-h-72 overflow-y-auto divide-y divide-slate-100 text-xs">
                        @if ($isAdminNav)
                            <!-- 1. WhatsApp Cola (Solo Admin) -->
                            <div id="notif-wa-item" class="p-3.5 hover:bg-slate-50 transition-colors flex items-start gap-3 {{ $waPendientes > 0 ? 'bg-amber-50/40' : '' }}">
                                <div id="notif-wa-icon" class="w-8 h-8 rounded-xl {{ $waPendientes > 0 ? 'bg-amber-100 text-amber-600' : 'bg-green-50 text-green-600' }} flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-4 h-4 {{ $waPendientes > 0 ? 'animate-spin' : '' }}" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p id="notif-wa-title" class="font-bold text-slate-800">Mensajes de WhatsApp</p>
                                    <p id="notif-wa-text" class="text-slate-600 font-medium mt-0.5">
                                        @if ($waPendientes > 0)
                                            Enviando recordatorios... Quedan {{ $waPendientes }} mensaje(s) pendiente(s).
                                        @else
                                            Se han enviado todos los mensajes pendientes
                                        @endif
                                    </p>
                                    <span id="notif-wa-time" class="text-[10px] text-slate-400 mt-1 block">
                                        {{ $tiempoTextoWa }}
                                    </span>
                                </div>
                            </div>

                            <!-- 2. Sugerencias y Quejas Recibidas (Solo Admin) -->
                            <div class="p-3.5 hover:bg-slate-50 transition-colors flex items-start gap-3">
                                <div class="w-8 h-8 rounded-xl {{ $feedbacksPendientes > 0 ? 'bg-indigo-100 text-indigo-600' : 'bg-slate-100 text-slate-500' }} flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="font-bold text-slate-800">Sugerencias y Quejas</p>
                                    <p class="text-slate-500 mt-0.5">
                                        @if ($feedbacksPendientes > 0)
                                            Tienes {{ $feedbacksPendientes }} mensaje(s) nuevo(s) de usuarios sin revisar.
                                        @else
                                            No hay nuevos mensajes de usuarios pendientes.
                                        @endif
                                    </p>
                                    <span class="text-[10px] text-slate-400 mt-1 block">Hoy</span>
                                </div>
                            </div>

                            <!-- 3. Pedidos Pendientes de Cobro (Solo Admin) -->
                            <div class="p-3.5 hover:bg-slate-50 transition-colors flex items-start gap-3">
                                <div class="w-8 h-8 rounded-xl {{ $pedidosPendientes > 0 ? 'bg-emerald-100 text-emerald-600' : 'bg-slate-100 text-slate-500' }} flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="font-bold text-slate-800">Pedidos por Conciliar</p>
                                    <p class="text-slate-500 mt-0.5">
                                        @if ($pedidosPendientes > 0)
                                            Hay {{ $pedidosPendientes }} pedido(s) pendientes de cobro en el sistema.
                                        @else
                                            Todos los pedidos en el sistema están conciliados.
                                        @endif
                                    </p>
                                    <span class="text-[10px] text-slate-400 mt-1 block">Hoy</span>
                                </div>
                            </div>
                        @else
                            <!-- Notificaciones Personalizadas del Usuario Estándar (!isAdmin) -->

                            <!-- 1. Estado de Pedidos del Usuario -->
                            <div class="p-3.5 hover:bg-slate-50 transition-colors flex items-start gap-3 {{ $misPedidosPendientes > 0 ? 'bg-amber-50/40' : '' }}">
                                <div class="w-8 h-8 rounded-xl {{ $misPedidosPendientes > 0 ? 'bg-amber-100 text-amber-600' : 'bg-green-50 text-green-600' }} flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="font-bold text-slate-800">Estado de tus Pedidos</p>
                                    <p class="text-slate-600 font-medium mt-0.5">
                                        @if ($misPedidosPendientes > 0)
                                            Tienes {{ $misPedidosPendientes }} pedido(s) pendiente(s) de pago.
                                        @else
                                            Todos tus pedidos realizados se encuentran pagados.
                                        @endif
                                    </p>
                                    <span class="text-[10px] text-slate-400 mt-1 block">Actualizado</span>
                                </div>
                            </div>

                            <!-- 2. Sus Sugerencias / Quejas Atendidas -->
                            <div class="p-3.5 hover:bg-slate-50 transition-colors flex items-start gap-3">
                                <div class="w-8 h-8 rounded-xl {{ $misFeedbacksAtendidos > 0 ? 'bg-indigo-100 text-indigo-600' : 'bg-slate-100 text-slate-500' }} flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="font-bold text-slate-800">Tus Sugerencias y Quejas</p>
                                    <p class="text-slate-500 mt-0.5">
                                        @if ($misFeedbacksAtendidos > 0)
                                            El administrador revisó o respondió {{ $misFeedbacksAtendidos }} de tus mensajes.
                                        @else
                                            No tienes mensajes pendientes o en revisión.
                                        @endif
                                    </p>
                                    <span class="text-[10px] text-slate-400 mt-1 block">Actualizado</span>
                                </div>
                            </div>

                            <!-- 3. Pagos Confirmados del Mes -->
                            <div class="p-3.5 hover:bg-slate-50 transition-colors flex items-start gap-3">
                                <div class="w-8 h-8 rounded-xl {{ $misPagosMes > 0 ? 'bg-emerald-100 text-emerald-600' : 'bg-slate-100 text-slate-500' }} flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <p class="font-bold text-slate-800">Tus Pagos Confirmados</p>
                                    <p class="text-slate-500 mt-0.5">
                                        @if ($misPagosMes > 0)
                                            Tienes {{ $misPagosMes }} abono(s)/pago(s) confirmados este mes.
                                        @else
                                            No tienes abonos registrados en el mes actual.
                                        @endif
                                    </p>
                                    <span class="text-[10px] text-slate-400 mt-1 block">Este mes</span>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="px-4 py-2 bg-slate-50 border-t border-slate-100 text-center">
                        <button @click="notifOpen = false" class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800">
                            Marcar todas como leídas
                        </button>
                    </div>
                </div>
            </div>

            <div class="h-6 w-px bg-slate-200 hidden sm:block"></div>

            <!-- Botón Foto de Perfil / Modal -->
            <div class="flex items-center mr-1">
                @if(Auth::user()->image)
                    <button @click="$dispatch('open-global-photo-modal')" class="focus:outline-none cursor-pointer transform hover:scale-105 transition-transform" title="Ver foto de perfil">
                        <img src="{{ route('user.image', Auth::user()->id) }}?v={{ Auth::user()->updated_at?->timestamp }}" class="w-9 h-9 rounded-xl object-cover shadow-sm border border-slate-200" alt="Foto de perfil">
                    </button>
                @else
                    <div class="w-9 h-9 rounded-xl bg-indigo-600 text-white font-bold flex items-center justify-center text-sm shadow-sm">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                @endif
            </div>

            <!-- Dropdown Perfil de Usuario -->
            <x-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <button class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-slate-100 transition-colors focus:outline-none cursor-pointer">
                        <span class="hidden sm:inline-block font-semibold text-sm text-slate-700 ml-1">{{ Auth::user()->name }}</span>
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </x-slot>

                <x-slot name="content">
                    <x-dropdown-link :href="route('profile.edit')">
                        {{ __('Perfil') }}
                    </x-dropdown-link>

                    <!-- Authentication -->
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-dropdown-link :href="route('logout')"
                            onclick="event.preventDefault(); this.closest('form').submit();"
                            class="text-red-600 font-semibold">
                            {{ __('Cerrar Sesión') }}
                        </x-dropdown-link>
                    </form>
                </x-slot>
            </x-dropdown>
        </div>

    </div>
</header>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        @if($isAdminNav)
        let lastPendingWa = {{ $waPendientes }};
        
        setInterval(function() {
            fetch("{{ route('admin.configuracion.wa-status') }}")
                .then(r => r.json())
                .then(data => {
                    const pendientes = data.pending_count || 0;
                    const elText = document.getElementById('notif-wa-text');
                    const elTitle = document.getElementById('notif-wa-title');
                    const elIcon = document.getElementById('notif-wa-icon');
                    const elItem = document.getElementById('notif-wa-item');
                    
                    if (elText) {
                        if (pendientes > 0) {
                            elText.textContent = 'Enviando recordatorios... Quedan ' + pendientes + ' mensaje(s) pendiente(s).';
                            if (elIcon) elIcon.className = 'w-8 h-8 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center flex-shrink-0 mt-0.5';
                            if (elItem) elItem.className = 'p-3.5 hover:bg-slate-50 transition-colors flex items-start gap-3 bg-amber-50/40';
                        } else {
                            elText.textContent = 'Se han enviado todos los mensajes pendientes';
                            if (elIcon) elIcon.className = 'w-8 h-8 rounded-xl bg-green-50 text-green-600 flex items-center justify-center flex-shrink-0 mt-0.5';
                            if (elItem) elItem.className = 'p-3.5 hover:bg-slate-50 transition-colors flex items-start gap-3';
                        }
                    }
                    
                    // Si el conteo anterior era > 0 y acaba de terminar (llegó a 0), lanzamos notificación flotante
                    if (lastPendingWa > 0 && pendientes === 0) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: '¡Envíos completados!',
                                text: 'Se han enviado todos los mensajes pendientes',
                                showConfirmButton: false,
                                timer: 5000,
                                timerProgressBar: true
                            });
                        }
                    }
                    lastPendingWa = pendientes;
                })
                .catch(() => {});
        }, 4000);
        @endif
    });

    let deferredPrompt = null;
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        const installBtn = document.getElementById('pwa-install-btn');
        if (installBtn) {
            installBtn.style.display = 'inline-flex';
        }
    });

    document.addEventListener('DOMContentLoaded', () => {
        const installBtn = document.getElementById('pwa-install-btn');
        if (installBtn) {
            installBtn.addEventListener('click', () => {
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    deferredPrompt.userChoice.then((choiceResult) => {
                        if (choiceResult.outcome === 'accepted') {
                            installBtn.style.display = 'none';
                        }
                        deferredPrompt = null;
                    });
                }
            });
        }
    });
</script>
