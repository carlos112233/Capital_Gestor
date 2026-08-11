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
                    PANEL ADMINISTRATIVO
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8l-8 8-8-8" />
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
                    class="absolute -right-12 sm:right-0 mt-2 w-[300px] sm:w-96 bg-white rounded-2xl shadow-xl border border-slate-200/80 py-2 z-[60] overflow-hidden"
                    style="display: none;">

                    <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            Tus Notificaciones
                        </h4>
                        @php
                            $unreadCount = Auth::user()->unreadNotifications->count();
                        @endphp
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600 border border-indigo-100">
                            {{ $unreadCount > 0 ? $unreadCount . ' Nuevas' : 'Al día' }}
                        </span>
                    </div>

                    <div class="max-h-72 overflow-y-auto divide-y divide-slate-100 text-xs">
                        @forelse(Auth::user()->unreadNotifications as $notification)
                            <a href="{{ $notification->data['action_url'] ?? '#' }}" class="p-3.5 hover:bg-slate-50 transition-colors flex items-start gap-3 bg-indigo-50/30 block">
                                <div class="w-8 h-8 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <img src="{{ $notification->data['icon_url'] ?? '/img/icon-192.png' }}" class="w-5 h-5 rounded-md" alt="icon">
                                </div>
                                <div class="flex-1">
                                    <p class="font-bold text-slate-800">{{ $notification->data['title'] ?? 'Nueva Notificación' }}</p>
                                    <p class="text-slate-600 font-medium mt-0.5">{{ $notification->data['message'] ?? '' }}</p>
                                    <span class="text-[10px] text-slate-400 mt-1 block">
                                        {{ $notification->created_at->diffForHumans() }}
                                    </span>
                                </div>
                            </a>
                        @empty
                            <div class="p-4 text-center text-slate-500">
                                No tienes notificaciones nuevas.
                            </div>
                        @endforelse
                    </div>

                    <div class="px-4 py-2 bg-slate-50 border-t border-slate-100 flex justify-between items-center">
                        <button id="enable-push-btn" class="text-[11px] font-bold text-emerald-600 hover:text-emerald-800" style="display: none;">
                            Activar Alertas Push
                        </button>
                        <form method="POST" action="{{ route('notifications.markAllRead') ?? '#' }}" class="inline">
                            @csrf
                            <button type="submit" class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800">
                                Marcar como leídas
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="h-6 w-px bg-slate-200 hidden sm:block"></div>

            <!-- Botón Foto de Perfil / Modal -->
            <div class="flex items-center mr-1">
                @if(Auth::user()->image)
                <button @click="$dispatch('open-global-photo-modal')" class="block flex-shrink-0 focus:outline-none cursor-pointer transform hover:scale-105 transition-transform" title="Ver foto de perfil">
                    <img src="{{ route('user.image', Auth::user()->id) }}?v={{ Auth::user()->updated_at?->timestamp }}" class="w-9 h-9 rounded-xl object-cover shadow-sm border border-slate-200 flex-shrink-0 block" alt="Foto de perfil">
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
        let lastPendingWa = {
            {
                $waPendientes
            }
        };

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
        
        // Push Notifications Logic
        const pushBtn = document.getElementById('enable-push-btn');
        if (pushBtn && 'serviceWorker' in navigator && 'PushManager' in window) {
            pushBtn.style.display = 'inline-block';
            
            // Convertir VAPID a Uint8Array
            function urlBase64ToUint8Array(base64String) {
                const padding = '='.repeat((4 - base64String.length % 4) % 4);
                const base64 = (base64String + padding)
                    .replace(/\-/g, '+')
                    .replace(/_/g, '/');

                const rawData = window.atob(base64);
                const outputArray = new Uint8Array(rawData.length);

                for (let i = 0; i < rawData.length; ++i) {
                    outputArray[i] = rawData.charCodeAt(i);
                }
                return outputArray;
            }
            
            // Revisar si ya está suscrito
            navigator.serviceWorker.ready.then(function(registration) {
                registration.pushManager.getSubscription().then(function(subscription) {
                    if (subscription) {
                        pushBtn.style.display = 'none'; // Ya está suscrito
                    }
                });
            });

            pushBtn.addEventListener('click', () => {
                Notification.requestPermission().then(permission => {
                    if (permission === 'granted') {
                        navigator.serviceWorker.ready.then(function(registration) {
                            const vapidPublicKey = '{{ env('VAPID_PUBLIC_KEY') }}';
                            const convertedVapidKey = urlBase64ToUint8Array(vapidPublicKey);
                            
                            registration.pushManager.subscribe({
                                userVisibleOnly: true,
                                applicationServerKey: convertedVapidKey
                            }).then(function(subscription) {
                                // Enviar a Laravel
                                fetch('{{ route('push.subscribe') }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    },
                                    body: JSON.stringify(subscription)
                                }).then(() => {
                                    pushBtn.style.display = 'none';
                                    if (typeof Swal !== 'undefined') {
                                        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Notificaciones activadas', showConfirmButton: false, timer: 3000 });
                                    }
                                });
                            });
                        });
                    }
                });
            });
        }
    });
</script>