@php
    try {
        $waPendientes = \Illuminate\Support\Facades\DB::table('whatsapp_pending_messages')->where('status', 'pendiente')->count();
        $waEnviados = \Illuminate\Support\Facades\DB::table('whatsapp_pending_messages')->where('status', 'enviado')->count();
        $ultimoWa = \Illuminate\Support\Facades\DB::table('whatsapp_pending_messages')->orderBy('updated_at', 'desc')->first();
    } catch (\Throwable $e) {
        $waPendientes = 0;
        $waEnviados = 0;
        $ultimoWa = null;
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

        <!-- Derecha: Notificaciones & Menú del Usuario -->
        <div class="flex items-center gap-3 sm:gap-4">
            
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
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Notificaciones del Sistema
                        </h4>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600 border border-indigo-100">3 Nuevas</span>
                    </div>

                    <div class="max-h-72 overflow-y-auto divide-y divide-slate-100 text-xs">
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
                                    {{ $ultimoWa ? \Carbon\Carbon::parse($ultimoWa->updated_at)->diffForHumans() : 'Hoy' }}
                                </span>
                            </div>
                        </div>

                        <div class="p-3.5 hover:bg-slate-50 transition-colors flex items-start gap-3">
                            <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="font-bold text-slate-800">Nueva Venta / Pedido</p>
                                <p class="text-slate-500 mt-0.5">Se registraron compras recientes en la plataforma.</p>
                                <span class="text-[10px] text-slate-400 mt-1 block">Hace 15 minutos</span>
                            </div>
                        </div>

                        <div class="p-3.5 hover:bg-slate-50 transition-colors flex items-start gap-3">
                            <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="font-bold text-slate-800">Conciliación de Saldo</p>
                                <p class="text-slate-500 mt-0.5">Cobro registrado con éxito en el sistema.</p>
                                <span class="text-[10px] text-slate-400 mt-1 block">Hace 1 hora</span>
                            </div>
                        </div>
                    </div>

                    <div class="px-4 py-2 bg-slate-50 border-t border-slate-100 text-center">
                        <button @click="notifOpen = false" class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800">
                            Marcar todas como leídas
                        </button>
                    </div>
                </div>
            </div>

            <div class="h-6 w-px bg-slate-200 hidden sm:block"></div>

            <!-- Dropdown Perfil de Usuario -->
            <x-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <button class="flex items-center gap-2.5 p-1.5 rounded-xl hover:bg-slate-100 transition-colors focus:outline-none cursor-pointer">
                        <div class="w-8 h-8 rounded-lg bg-indigo-600 text-white font-bold flex items-center justify-center text-xs shadow-sm">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
                        <span class="hidden sm:inline-block font-semibold text-sm text-slate-700">{{ Auth::user()->name }}</span>
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
    });
</script>
