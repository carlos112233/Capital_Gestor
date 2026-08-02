<!-- Sidebar Móvil (Drawer) -->
<div x-show="mobileOpen" 
     x-transition:enter="transition-opacity ease-linear duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-linear duration-300"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-40 bg-gray-900/80 lg:hidden" 
     @click="mobileOpen = false" 
     style="display: none;"></div>

<!-- Sidebar Principal -->
<aside :class="sidebarOpen ? 'w-64' : 'w-20'"
       class="fixed inset-y-0 left-0 z-50 flex flex-col bg-slate-900 text-slate-300 transition-all duration-300 ease-in-out shadow-2xl border-r border-slate-800/80 hidden lg:flex">
    
    <!-- Logo & Brand Header -->
    <div class="flex items-center h-20 bg-slate-950/80 border-b border-slate-800/80 transition-all duration-300"
         :class="sidebarOpen ? 'justify-between px-4' : 'justify-center px-2'">
        <a href="{{ Auth::user()->hasRole('admin') ? route('dashboardAdmin') : route('dashboard') }}" class="flex items-center gap-3 overflow-hidden">
            <div class="flex-shrink-0 w-12 h-12 rounded-2xl bg-slate-950 shadow-[0_0_18px_rgba(99,102,241,0.45)] p-1.5 flex items-center justify-center transition-all duration-300 hover:scale-105 border-2 border-indigo-500">
                <img src="{{ file_exists(public_path('img/Logo.svg')) ? asset('img/Logo.svg') : asset('img/Logo.png') }}?v={{ time() }}" class="h-9 w-9 object-contain" alt="Logo Comida Rápida">
            </div>
            <div x-show="sidebarOpen" x-transition class="flex flex-col whitespace-nowrap">
                <span class="font-extrabold text-white text-base tracking-wide leading-tight">Capital Gestor</span>
                <span class="text-[11px] text-indigo-400 font-extrabold tracking-wider uppercase mt-0.5">PANEL ADMIN</span>
            </div>
        </a>
        <button @click="sidebarOpen = !sidebarOpen" 
                x-show="sidebarOpen" x-transition
                class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 focus:outline-none transition-colors ml-auto"
                title="Colapsar menú">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 5l-7 7 7 7M19 5l-7 7 7 7" />
            </svg>
        </button>
    </div>

    <!-- Scrollable Navigation Items -->
    <div class="flex-1 overflow-y-auto px-3 py-4 space-y-6 custom-scrollbar">
        
        <!-- Grupo 1: PRINCIPAL -->
        <div>
            <div x-show="sidebarOpen" x-transition class="px-3 mb-2 text-[11px] font-bold tracking-wider text-slate-400 uppercase">
                Principal
            </div>
            <nav class="space-y-1">
                @if (Auth::user()->hasRole('admin'))
                    <a href="{{ route('dashboardAdmin') }}"
                       class="flex items-center px-3 py-2.5 rounded-xl font-medium text-sm transition-all duration-200 group {{ request()->routeIs('dashboardAdmin') ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-500/25' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                        <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('dashboardAdmin') ? 'text-white' : 'text-indigo-400 group-hover:text-white' }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span x-show="sidebarOpen" x-transition class="ml-3 whitespace-nowrap">Dashboard</span>
                    </a>
                @else
                    <a href="{{ route('dashboard') }}"
                       class="flex items-center px-3 py-2.5 rounded-xl font-medium text-sm transition-all duration-200 group {{ request()->routeIs('dashboard') ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-500/25' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                        <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('dashboard') ? 'text-white' : 'text-indigo-400 group-hover:text-white' }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span x-show="sidebarOpen" x-transition class="ml-3 whitespace-nowrap">Dashboard</span>
                    </a>
                @endif

                <a href="{{ route('catalogo.index') }}"
                   class="flex items-center px-3 py-2.5 rounded-xl font-medium text-sm transition-all duration-200 group {{ request()->routeIs('catalogo.index') ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-500/25' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                    <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('catalogo.index') ? 'text-white' : 'text-amber-400 group-hover:text-white' }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <span x-show="sidebarOpen" x-transition class="ml-3 whitespace-nowrap">Existencias</span>
                </a>
            </nav>
        </div>

        <!-- Grupo 2: OPERACIONES -->
        <div>
            <div x-show="sidebarOpen" x-transition class="px-3 mb-2 text-[11px] font-bold tracking-wider text-slate-400 uppercase">
                Operaciones
            </div>
            <nav class="space-y-1">
                <a href="{{ route('ventas.index') }}"
                   class="flex items-center px-3 py-2.5 rounded-xl font-medium text-sm transition-all duration-200 group {{ request()->routeIs('ventas.*') ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-500/25' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                    <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('ventas.*') ? 'text-white' : 'text-emerald-400 group-hover:text-white' }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span x-show="sidebarOpen" x-transition class="ml-3 whitespace-nowrap">
                        {{ Auth::user()->hasRole('admin') ? 'Ventas Realizadas' : 'Compras Realizadas' }}
                    </span>
                </a>

                <a href="{{ route('pedidos.index') }}"
                   class="flex items-center px-3 py-2.5 rounded-xl font-medium text-sm transition-all duration-200 group {{ request()->routeIs('pedidos.*') ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-500/25' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                    <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('pedidos.*') ? 'text-white' : 'text-cyan-400 group-hover:text-white' }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    <span x-show="sidebarOpen" x-transition class="ml-3 whitespace-nowrap">Pedidos</span>
                </a>
            </nav>
        </div>

        <!-- Grupo 3: ADMINISTRACIÓN (Solo Admin) -->
        @if (Auth::user()->hasRole('admin'))
            <div>
                <div x-show="sidebarOpen" x-transition class="px-3 mb-2 text-[11px] font-bold tracking-wider text-slate-400 uppercase">
                    Administración
                </div>
                <nav class="space-y-1">
                    <a href="{{ route('admin.entradas.index') }}"
                       class="flex items-center px-3 py-2.5 rounded-xl font-medium text-sm transition-all duration-200 group {{ request()->routeIs('admin.entradas.*') ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-500/25' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                        <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('admin.entradas.*') ? 'text-white' : 'text-purple-400 group-hover:text-white' }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 11l3-3m0 0l3 3m-3-3v8m0-13a9 9 0 110 18 9 9 0 010-18z" />
                        </svg>
                        <span x-show="sidebarOpen" x-transition class="ml-3 whitespace-nowrap">Entradas Capital</span>
                    </a>

                    <a href="{{ route('admin.articulos.index') }}"
                       class="flex items-center px-3 py-2.5 rounded-xl font-medium text-sm transition-all duration-200 group {{ request()->routeIs('admin.articulos.*') ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-500/25' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                        <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('admin.articulos.*') ? 'text-white' : 'text-pink-400 group-hover:text-white' }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        <span x-show="sidebarOpen" x-transition class="ml-3 whitespace-nowrap">Artículos</span>
                    </a>

                    <a href="{{ route('admin.clientes.index') }}"
                       class="flex items-center px-3 py-2.5 rounded-xl font-medium text-sm transition-all duration-200 group {{ request()->routeIs('admin.clientes.*') ? 'bg-gradient-to-r from-indigo-600 to-indigo-500 text-white shadow-lg shadow-indigo-500/25' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                        <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('admin.clientes.*') ? 'text-white' : 'text-teal-400 group-hover:text-white' }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span x-show="sidebarOpen" x-transition class="ml-3 whitespace-nowrap">Clientes</span>
                    </a>
                </nav>
            </div>
        @endif

        <!-- Grupo 4: SISTEMA Y AJUSTES -->
        <div>
            <div x-show="sidebarOpen" x-transition class="px-3 mb-2 text-[11px] font-bold tracking-wider text-slate-400 uppercase">
                Sistema
            </div>
            <nav class="space-y-1">
                <a href="{{ route('cobros.index') }}"
                   class="flex items-center px-3 py-2.5 rounded-xl font-medium text-sm transition-all duration-200 group {{ request()->routeIs('cobros.*') ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-lg shadow-emerald-500/25' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                    <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('cobros.*') ? 'text-white' : 'text-emerald-400 group-hover:text-white' }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span x-show="sidebarOpen" x-transition class="ml-3 whitespace-nowrap">Cobros y Pagos</span>
                </a>

                <a href="{{ route('datos.index') }}"
                   class="flex items-center px-3 py-2.5 rounded-xl font-medium text-sm transition-all duration-200 group {{ request()->routeIs('datos.*') ? 'bg-gradient-to-r from-slate-700 to-slate-800 text-white shadow-lg shadow-slate-700/25' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                    <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('datos.*') ? 'text-white' : 'text-blue-400 group-hover:text-white' }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    <span x-show="sidebarOpen" x-transition class="ml-3 whitespace-nowrap">Método de pago</span>
                </a>

                @if (Auth::user()->hasRole('admin'))
                    <a href="{{ route('admin.configuracion.index') }}"
                       class="flex items-center px-3 py-2.5 rounded-xl font-medium text-sm transition-all duration-200 group {{ request()->routeIs('admin.configuracion.*') ? 'bg-gradient-to-r from-amber-600 to-orange-600 text-white shadow-lg shadow-amber-500/25' : 'text-slate-300 hover:bg-slate-800/60 hover:text-white' }}">
                        <svg class="w-5 h-5 flex-shrink-0 {{ request()->routeIs('admin.configuracion.*') ? 'text-white' : 'text-amber-400 group-hover:text-white' }} transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span x-show="sidebarOpen" x-transition class="ml-3 whitespace-nowrap">Cambiar Logo</span>
                    </a>
                @endif
            </nav>
        </div>

    </div>

    <!-- User Profile Footer -->
    <div class="p-3 bg-slate-950/80 border-t border-slate-800/80">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 overflow-hidden">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-500 to-indigo-700 flex items-center justify-center text-white font-bold text-sm shadow-md flex-shrink-0">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </div>
                <div x-show="sidebarOpen" x-transition class="flex flex-col min-w-0">
                    <span class="text-xs font-semibold text-white truncate">{{ Auth::user()->name }}</span>
                    <span class="text-[10px] text-slate-400 truncate">{{ Auth::user()->email }}</span>
                </div>
            </div>
            
            <form method="POST" action="{{ route('logout') }}" x-show="sidebarOpen" x-transition>
                @csrf
                <button type="submit" 
                        class="p-2 rounded-lg text-slate-400 hover:text-red-400 hover:bg-red-500/10 focus:outline-none transition-colors" 
                        title="Cerrar sesión">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </button>
            </form>
        </div>
    </div>
</aside>

<!-- Sidebar Drawer en Pantallas Móviles -->
<aside x-show="mobileOpen"
       x-transition:enter="transition ease-out duration-300 transform"
       x-transition:enter-start="-translate-x-full"
       x-transition:enter-end="translate-x-0"
       x-transition:leave="transition ease-in duration-300 transform"
       x-transition:leave-start="translate-x-0"
       x-transition:leave-end="-translate-x-full"
       class="fixed inset-y-0 left-0 z-50 flex flex-col w-64 bg-slate-900 text-slate-300 shadow-2xl border-r border-slate-800 lg:hidden"
       style="display: none;">
    
    <div class="flex items-center justify-between h-16 px-4 bg-slate-950 border-b border-slate-800">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
            <img src="{{ file_exists(public_path('img/Logo.svg')) ? asset('img/Logo.svg') : asset('img/Logo.png') }}?v={{ time() }}" class="h-8 w-auto object-contain" alt="Logo">
            <span class="font-bold text-white text-base">Capital Gestor</span>
        </a>
        <button @click="mobileOpen = false" class="p-1 rounded-lg text-slate-400 hover:text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto px-3 py-4 space-y-4">
        @if (Auth::user()->hasRole('admin'))
            <a href="{{ route('dashboardAdmin') }}" class="flex items-center px-3 py-2.5 rounded-xl font-medium text-sm text-slate-200 hover:bg-slate-800">Dashboard</a>
        @else
            <a href="{{ route('dashboard') }}" class="flex items-center px-3 py-2.5 rounded-xl font-medium text-sm text-slate-200 hover:bg-slate-800">Dashboard</a>
        @endif
        <a href="{{ route('catalogo.index') }}" class="flex items-center px-3 py-2.5 rounded-xl font-medium text-sm text-slate-200 hover:bg-slate-800">Existencias</a>
        <a href="{{ route('ventas.index') }}" class="flex items-center px-3 py-2.5 rounded-xl font-medium text-sm text-slate-200 hover:bg-slate-800">Ventas Realizadas</a>
        <a href="{{ route('pedidos.index') }}" class="flex items-center px-3 py-2.5 rounded-xl font-medium text-sm text-slate-200 hover:bg-slate-800">Pedidos</a>
        @if (Auth::user()->hasRole('admin'))
            <a href="{{ route('admin.entradas.index') }}" class="flex items-center px-3 py-2.5 rounded-xl font-medium text-sm text-slate-200 hover:bg-slate-800">Entradas Capital</a>
            <a href="{{ route('admin.articulos.index') }}" class="flex items-center px-3 py-2.5 rounded-xl font-medium text-sm text-slate-200 hover:bg-slate-800">Artículos</a>
            <a href="{{ route('admin.clientes.index') }}" class="flex items-center px-3 py-2.5 rounded-xl font-medium text-sm text-slate-200 hover:bg-slate-800">Clientes</a>
        @endif
        <a href="{{ route('datos.index') }}" class="flex items-center px-3 py-2.5 rounded-xl font-medium text-sm text-slate-200 hover:bg-slate-800">Método de pago</a>
    </div>

    <div class="p-4 bg-slate-950 border-t border-slate-800">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full text-left text-sm font-semibold text-red-400 hover:text-red-300">Cerrar Sesión</button>
        </form>
    </div>
</aside>
