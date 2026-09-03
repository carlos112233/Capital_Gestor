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

            <!-- Carrito de Compras -->
            @if(!Auth::user()->hasRole('admin'))
            <div class="relative" x-data="shoppingCart()" @add-to-cart.window="addItem($event.detail)">
                <button id="tour-btn-carrito" @click="cartOpen = !cartOpen" @click.away="cartOpen = false"
                    class="relative p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors focus:outline-none cursor-pointer"
                    title="Carrito de Compras">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span x-show="itemCount > 0" x-text="itemCount" x-transition
                        class="absolute top-1 right-1 flex items-center justify-center min-w-[16px] h-4 px-1 rounded-full bg-rose-500 text-[10px] font-bold text-white border border-white shadow-sm" style="display: none;">
                    </span>
                </button>

                <!-- Dropdown Carrito -->
                <div x-show="cartOpen" x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute -right-12 sm:right-0 mt-2 w-[320px] sm:w-96 bg-white rounded-2xl shadow-xl border border-slate-200/80 py-2 z-[60] overflow-hidden"
                    style="display: none;">
                    
                    <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                            Tu Carrito
                        </h4>
                        <button @click="clearCart()" class="text-[10px] font-bold text-rose-600 hover:text-rose-800">Vaciar</button>
                    </div>

                    <div class="max-h-72 overflow-y-auto divide-y divide-slate-100 text-xs">
                        <template x-if="items.length === 0">
                            <div class="p-4 text-center text-slate-500">El carrito está vacío.</div>
                        </template>
                        <template x-for="(item, index) in items" :key="index">
                            <div class="p-3.5 flex items-start justify-between gap-3 bg-white hover:bg-slate-50 transition-colors">
                                <div class="flex-1">
                                    <p class="font-bold text-slate-800" x-text="item.nombre"></p>
                                    <p class="text-slate-600 font-medium mt-0.5" x-text="'$ ' + parseFloat(item.precio).toFixed(2)"></p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <input type="number" x-model.number="item.cantidad" @change="saveCart()" min="1" class="w-16 h-7 text-xs border-slate-200 rounded-md text-center py-0 px-1">
                                    <button @click="removeItem(index)" class="text-rose-500 hover:text-rose-700 p-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="px-4 py-3 bg-slate-50 border-t border-slate-100">
                        <div class="flex justify-between items-center mb-3">
                            <span class="font-bold text-slate-700">Total:</span>
                            <span class="font-bold text-lg text-indigo-600" x-text="'$ ' + total.toFixed(2)"></span>
                        </div>
                        <form method="POST" action="{{ route('ventas.storeMultiple') }}">
                            @csrf
                            <input type="hidden" name="redirect_to" value="{{ route('ventas.index') }}">
                            <template x-for="(item, index) in items" :key="index">
                                <div>
                                    <input type="hidden" :name="`ventas[${index}][articulo_id]`" :value="item.id">
                                    <input type="hidden" :name="`ventas[${index}][cantidad]`" :value="item.cantidad">
                                    <input type="hidden" :name="`ventas[${index}][precio]`" :value="item.precio">
                                </div>
                            </template>
                            <button type="submit" :disabled="items.length === 0"
                                class="w-full inline-flex items-center justify-center px-4 py-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-sm shadow-md transition-all cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                                Proceder al Pago
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endif

            <!-- Icono de Alertas / Notificaciones Interactivo -->
            <div class="relative" x-data="{ notifOpen: false }">
                @php
                    $unreadCount = Auth::user()->unreadNotifications->count();
                @endphp
                <button id="tour-btn-notificaciones" @click="notifOpen = !notifOpen" @click.away="notifOpen = false"
                    class="relative p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors focus:outline-none cursor-pointer"
                    title="Notificaciones">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 01-6 0v-1m6 0H9" />
                    </svg>
                    @if($unreadCount > 0)
                        <span class="absolute top-1 right-1 flex items-center justify-center min-w-[16px] h-4 px-1 rounded-full bg-emerald-500 text-[10px] font-bold text-white border border-white shadow-sm">
                            {{ $unreadCount }}
                        </span>
                    @endif
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
                <button @click="$dispatch('open-global-photo-modal')" class="block flex-shrink-0 focus:outline-none cursor-pointer transform hover:scale-105 transition-transform" title="Ver foto de perfil">
                    <img src="{{ route('user.image', Auth::user()->id) }}?v={{ Auth::user()->updated_at?->timestamp }}" class="w-9 h-9 rounded-xl object-cover shadow-sm border border-slate-200 flex-shrink-0 block" alt="Foto de perfil">
                </button>
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
        let lastPendingWa = {{ $waPendientes ?? 0 }};

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
                const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
                const rawData = window.atob(base64);
                const outputArray = new Uint8Array(rawData.length);
                for (let i = 0; i < rawData.length; ++i) { outputArray[i] = rawData.charCodeAt(i); }
                return outputArray;
            }
            
            navigator.serviceWorker.ready.then(function(registration) {
                registration.pushManager.getSubscription().then(function(subscription) {
                    if (subscription) {
                        pushBtn.style.display = 'none'; // Ya está suscrito
                        fetch('{{ route('push.subscribe') }}', {
                            method: 'POST',
                            headers: { 
                                'Content-Type': 'application/json', 
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}' 
                            },
                            body: JSON.stringify(subscription)
                        }).catch(() => {}); // Sincronización silenciosa
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
                                fetch('{{ route('push.subscribe') }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    },
                                    body: JSON.stringify(subscription)
                                }).then(() => {
                                    pushBtn.style.display = 'none';
                                    if (typeof Swal !== 'undefined') Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Activado', showConfirmButton: false, timer: 3000 });
                                });
                            }).catch(() => {});
                        });
                    }
                });
            });
        }
    });

    function shoppingCart() {
        return {
            cartOpen: false,
            items: JSON.parse(localStorage.getItem('user_cart')) || [],
            init() {
                // Si venimos de una redirección exitosa (ej. compra concretada), vaciar el carrito.
                @if(session('success'))
                    this.clearCart();
                @endif
            },
            get itemCount() {
                return this.items.reduce((total, item) => total + parseInt(item.cantidad), 0);
            },
            get total() {
                return this.items.reduce((total, item) => total + (parseFloat(item.precio) * parseInt(item.cantidad)), 0);
            },
            saveCart() {
                localStorage.setItem('user_cart', JSON.stringify(this.items));
            },
            addItem(newItem) {
                let existingItem = this.items.find(i => i.id === newItem.id);
                if (existingItem) {
                    existingItem.cantidad += parseInt(newItem.cantidad);
                } else {
                    this.items.push(newItem);
                }
                this.saveCart();
                
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Agregado al carrito', showConfirmButton: false, timer: 2000 });
                }
            },
            removeItem(index) {
                this.items.splice(index, 1);
                this.saveCart();
            },
            clearCart() {
                this.items = [];
                this.saveCart();
            }
        }
    }

    @php
        $hasSeenCarrito = Auth::check() && Auth::user()->tutorials()->where('tutorial_name', 'carrito')->exists();
        $hasSeenNotif = Auth::check() && Auth::user()->tutorials()->where('tutorial_name', 'notificaciones')->exists();
    @endphp

    document.addEventListener('DOMContentLoaded', function () {
        const urlParams = new URLSearchParams(window.location.search);
        const forceTutorial = urlParams.get('tutorial');
        const hasSeenCarrito = @json($hasSeenCarrito);
        const hasSeenNotif = @json($hasSeenNotif);

        // Si se pide explícitamente el tutorial del carrito o si estamos en una página y nunca lo ha visto
        if (forceTutorial === 'carrito' || (forceTutorial === 'true' && !hasSeenCarrito)) {
            const driverObj = window.driver.js.driver({
                showProgress: true,
                nextBtnText: 'Siguiente ➔',
                prevBtnText: '⬅ Anterior',
                doneBtnText: '¡Entendido!',
                steps: [
                    {
                        element: '#tour-btn-carrito',
                        popover: {
                            title: 'Carrito de Compras',
                            description: 'Todos los artículos que agregues al carrito aparecerán aquí. Haz clic para revisar tus productos y proceder al pago.',
                            side: "bottom",
                            align: 'end'
                        }
                    }
                ],
                onDestroyStarted: () => {
                    driverObj.destroy();
                    fetch('{{ route("tutorial.marcar-visto") ?? "#" }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ tutorial_name: 'carrito' })
                    });
                }
            });
            if (forceTutorial === 'carrito') {
                const url = new URL(window.location);
                url.searchParams.delete('tutorial');
                window.history.replaceState({}, '', url);
            }
            // Pequeño delay para asegurar que Driver.js esté listo
            setTimeout(() => { if(document.getElementById('tour-btn-carrito')) driverObj.drive(); }, 500);
        }

        // Si se pide explícitamente el tutorial de notificaciones
        if (forceTutorial === 'notificaciones' || (forceTutorial === 'true' && !hasSeenNotif && window.location.pathname === '/dashboard')) {
            const driverObj = window.driver.js.driver({
                showProgress: true,
                nextBtnText: 'Siguiente ➔',
                prevBtnText: '⬅ Anterior',
                doneBtnText: '¡Entendido!',
                steps: [
                    {
                        element: '#tour-btn-notificaciones',
                        popover: {
                            title: 'Tus Notificaciones',
                            description: 'Aquí te avisaremos cuando haya actualizaciones sobre tus pagos, pedidos o mensajes importantes.',
                            side: "bottom",
                            align: 'end'
                        }
                    }
                ],
                onDestroyStarted: () => {
                    driverObj.destroy();
                    fetch('{{ route("tutorial.marcar-visto") ?? "#" }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ tutorial_name: 'notificaciones' })
                    });
                }
            });
            if (forceTutorial === 'notificaciones') {
                const url = new URL(window.location);
                url.searchParams.delete('tutorial');
                window.history.replaceState({}, '', url);
            }
            setTimeout(() => { if(document.getElementById('tour-btn-notificaciones')) driverObj.drive(); }, 500);
        }

        // Si se pide explícitamente el tutorial de instalar app
        if (forceTutorial === 'instalar') {
            // Forzar que el botón sea visible temporalmente para el tutorial
            const pwaBtn = document.getElementById('pwa-install-btn');
            if (pwaBtn) {
                const wasHidden = pwaBtn.style.display === 'none';
                if (wasHidden) pwaBtn.style.display = 'inline-flex';

                const driverObj = window.driver.js.driver({
                    showProgress: false,
                    doneBtnText: '¡Entendido!',
                    steps: [
                        {
                            element: '#pwa-install-btn',
                            popover: {
                                title: 'Instalar Aplicación',
                                description: 'Si ves este botón, puedes hacer clic aquí para instalar nuestra plataforma en tu dispositivo y acceder más rápido como si fuera una app nativa.',
                                side: "bottom",
                                align: 'end'
                            }
                        }
                    ],
                    onDestroyStarted: () => {
                        if (wasHidden) pwaBtn.style.display = 'none';
                        driverObj.destroy();
                    }
                });

                const url = new URL(window.location);
                url.searchParams.delete('tutorial');
                window.history.replaceState({}, '', url);

                setTimeout(() => { driverObj.drive(); }, 500);
            }
        }
    });
</script>