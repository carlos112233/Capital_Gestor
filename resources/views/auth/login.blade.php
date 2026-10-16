<x-guest-layout>
    <!-- Estado de la Sesión -->
    <x-auth-session-status class="mb-4 text-center text-xs font-bold" :status="session('status')" />

    <!-- Encabezado de la Tarjeta: Logo y Marca Institucional -->
    <div class="flex justify-center mb-2" style="display: flex; justify-content: center; margin-bottom: 0.4rem;">
        <div class="w-14 h-14 rounded-2xl bg-[#FFF8E7] border border-amber-200/80 shadow-xs flex items-center justify-center p-2 transition-transform duration-300 hover:scale-105"
             style="width: 3.4rem; height: 3.4rem; border-radius: 0.9rem; background-color: #FFF8E7; border: 1px solid rgba(251, 191, 36, 0.5); display: flex; align-items: center; justify-content: center; padding: 0.5rem;">
            <img src="{{ $appSettings->logo_url ?? (file_exists(public_path('img/Logo.svg')) ? asset('img/Logo.svg') : asset('img/Logo.png')) }}?v={{ time() }}" 
                 class="w-full h-full object-contain" 
                 style="width: 100%; height: 100%; object-fit: contain;"
                 alt="{{ $appSettings->app_name ?? 'El Bajón' }} Logo">
        </div>
    </div>

    <!-- Título Institucional y Slogan -->
    <div class="text-center" style="text-align: center;">
        <span class="block text-xs font-black tracking-wider text-slate-800 uppercase leading-none" style="display: block; font-size: 0.75rem; font-weight: 900; letter-spacing: 0.05em; color: #1e293b; text-transform: uppercase;">
            {{ $appSettings->app_name ?? 'EL BAJÓN' }}
        </span>
        <span class="block text-[9px] font-extrabold tracking-widest text-amber-600 uppercase mt-1" style="display: block; font-size: 8.5px; font-weight: 800; letter-spacing: 0.1em; color: #d97706; text-transform: uppercase; margin-top: 0.2rem;">
            HAMBURGUESAS &bull; TORTAS &bull; CEMITAS &bull; PAN DE DULCE
        </span>
    </div>

    <!-- Bienvenida -->
    <div class="mt-3 mb-4 text-center" style="margin-top: 0.5rem; margin-bottom: 1rem; text-align: center;">
        <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight" style="font-size: 1.4rem; font-weight: 900; color: #0f172a; margin: 0; line-height: 1.2;">
            ¡Bienvenido de nuevo!
        </h2>
        <p class="text-xs text-slate-400 font-medium mt-0.5" style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.2rem; font-weight: 500;">
            Ingresa tus credenciales para acceder al sistema
        </p>
    </div>

    <!-- Formulario de Inicio de Sesión -->
    <form method="POST" action="{{ route('login') }}" class="space-y-3.5" style="display: flex; flex-direction: column; gap: 0.85rem;">
        @csrf

        <!-- Correo Electrónico -->
        <div>
            <label for="email" class="block text-[10px] font-bold text-slate-700 uppercase tracking-widest mb-1.5" style="display: block; font-size: 10px; font-weight: 700; color: #334155; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.35rem;">
                CORREO ELECTRÓNICO
            </label>
            <div class="relative flex items-center rounded-xl border border-slate-200 bg-white hover:border-slate-300 focus-within:border-violet-500 focus-within:ring-2 focus-within:ring-violet-500/20 transition-all"
                 style="position: relative; display: flex; align-items: center; border-radius: 0.75rem; border: 1px solid #e2e8f0; background: #ffffff;">
                <div class="pl-3.5 pr-2 flex items-center pointer-events-none text-slate-400" style="padding-left: 0.85rem; padding-right: 0.5rem; display: flex; align-items: center; color: #94a3b8;">
                    <span class="w-5 h-5 rounded-full border border-slate-300 flex items-center justify-center text-[11px] font-bold text-slate-400"
                          style="width: 1.25rem; height: 1.25rem; border-radius: 50%; border: 1px solid #cbd5e1; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: bold; color: #94a3b8;">
                        @
                    </span>
                </div>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                    placeholder="correo@ejemplo.com"
                    class="w-full py-2.5 pr-3.5 text-slate-800 text-sm font-medium border-0 focus:ring-0 outline-none bg-transparent placeholder-slate-400"
                    style="width: 100%; padding: 0.65rem 0.85rem 0.65rem 0; border: none; outline: none; background: transparent; font-size: 0.875rem; color: #1e293b;">
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-1 text-xs text-rose-500 font-semibold" />
        </div>

        <!-- Contraseña con botón Ver/Ocultar -->
        <div x-data="{ show: false }">
            <label for="password" class="block text-[10px] font-bold text-slate-700 uppercase tracking-widest mb-1.5" style="display: block; font-size: 10px; font-weight: 700; color: #334155; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.35rem;">
                CONTRASEÑA
            </label>
            <div class="relative flex items-center rounded-xl border border-slate-200 bg-white hover:border-slate-300 focus-within:border-violet-500 focus-within:ring-2 focus-within:ring-violet-500/20 transition-all"
                 style="position: relative; display: flex; align-items: center; border-radius: 0.75rem; border: 1px solid #e2e8f0; background: #ffffff;">
                <div class="pl-3.5 pr-2 flex items-center pointer-events-none text-slate-400" style="padding-left: 0.85rem; padding-right: 0.5rem; display: flex; align-items: center; color: #94a3b8;">
                    <svg class="w-4 h-4 text-slate-400" style="width: 1rem; height: 1rem; color: #94a3b8;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <input id="password" x-bind:type="show ? 'text' : 'password'" name="password" required autocomplete="current-password"
                    placeholder="••••••••••••••••"
                    class="w-full py-2.5 pr-2 text-slate-800 text-sm font-medium border-0 focus:ring-0 outline-none bg-transparent placeholder-slate-400"
                    style="width: 100%; padding: 0.65rem 0.5rem 0.65rem 0; border: none; outline: none; background: transparent; font-size: 0.875rem; color: #1e293b;">
                
                <button type="button" @click="show = !show" class="pr-3.5 flex items-center text-slate-400 hover:text-violet-600 focus:outline-none transition-colors"
                        style="padding-right: 0.85rem; display: flex; align-items: center; background: none; border: none; cursor: pointer; color: #94a3b8;"
                        title="Mostrar/Ocultar contraseña">
                    <svg x-show="!show" class="h-4 w-4" style="width: 1rem; height: 1rem;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg x-show="show" class="h-4 w-4" style="width: 1rem; height: 1rem; display: none;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                    </svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-1 text-xs text-rose-500 font-semibold" />
        </div>

        <!-- Recordarme & Olvidaste tu contraseña -->
        <div class="flex items-center justify-between pt-1" style="display: flex; align-items: center; justify-content: space-between; padding-top: 0.25rem;">
            <label for="remember_me" class="inline-flex items-center cursor-pointer group" style="display: inline-flex; align-items: center; cursor: pointer;">
                <input id="remember_me" type="checkbox" name="remember"
                    class="w-4 h-4 rounded border-slate-300 text-violet-600 focus:ring-violet-500 shadow-xs cursor-pointer"
                    style="width: 1rem; height: 1rem; border-radius: 0.25rem; accent-color: #7c3aed; cursor: pointer;">
                <span class="ms-2 text-xs font-medium text-slate-600 group-hover:text-slate-900 transition-colors" style="margin-left: 0.5rem; font-size: 0.75rem; color: #475569;">
                    Recordarme
                </span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-xs font-bold text-violet-600 hover:text-violet-700 transition-colors hover:underline"
                   style="font-size: 0.75rem; font-weight: 700; color: #7c3aed; text-decoration: none;"
                   href="{{ route('password.request') }}">
                    ¿Olvidaste tu contraseña?
                </a>
            @endif
        </div>

        <!-- Botón Iniciar Sesión -->
        <div class="pt-2" style="padding-top: 0.5rem;">
            <button type="submit"
                class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-gradient-to-r from-violet-600 via-indigo-600 to-violet-600 hover:from-violet-500 hover:to-indigo-500 text-white font-bold text-sm shadow-lg shadow-violet-500/30 hover:shadow-violet-500/40 transition-all duration-200 transform hover:-translate-y-0.5 focus:outline-none cursor-pointer"
                style="width: 100%; border-radius: 0.75rem; background: linear-gradient(135deg, #7c3aed 0%, #6366f1 50%, #7c3aed 100%); color: #ffffff; font-weight: 700; font-size: 0.875rem; padding: 0.8rem 1.25rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem; border: none; cursor: pointer; box-shadow: 0 10px 25px -5px rgba(124, 58, 237, 0.45);">
                <svg class="w-4 h-4" style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                </svg>
                Iniciar Sesión
            </button>
        </div>
    </form>

    <!-- Indicador de Estatus del Sistema (Pill inferior) -->
    <div class="mt-3.5 flex justify-center" style="margin-top: 0.85rem; display: flex; justify-content: center;">
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-slate-50 border border-slate-100 text-[11px] font-semibold text-slate-600 shadow-2xs"
             style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.35rem 0.9rem; border-radius: 9999px; background-color: #f8fafc; border: 1px solid #e2e8f0; font-size: 11px; font-weight: 600; color: #475569;">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" style="width: 0.5rem; height: 0.5rem; border-radius: 50%; background-color: #10b981; display: inline-block;"></span>
            <span>Sistema POS &amp; Cocina Activo</span>
        </div>
    </div>
</x-guest-layout>
