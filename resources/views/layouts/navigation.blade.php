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
            
            <!-- Icono de Alertas / Notificaciones -->
            <button class="relative p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors focus:outline-none" title="Notificaciones">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 01-6 0v-1m6 0H9" />
                </svg>
                <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-emerald-500 ring-2 ring-white"></span>
            </button>

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
