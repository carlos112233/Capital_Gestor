<section>
    <header class="mb-6">
        <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            Actualizar Contraseña
        </h2>
        <p class="mt-1 text-sm text-slate-500 font-medium">
            Asegúrate de utilizar una contraseña larga y compleja para mantener tu cuenta segura.
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        @method('put')

        <div>
            <label for="update_password_current_password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Contraseña Actual</label>
            <input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password"
                placeholder="••••••••"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 text-slate-800 text-sm font-medium transition-all outline-none bg-slate-50/50 hover:bg-white focus:bg-white">
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-1.5 text-xs text-rose-500 font-semibold" />
        </div>

        <div>
            <label for="update_password_password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Nueva Contraseña</label>
            <input id="update_password_password" name="password" type="password" autocomplete="new-password"
                placeholder="••••••••"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 text-slate-800 text-sm font-medium transition-all outline-none bg-slate-50/50 hover:bg-white focus:bg-white">
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-1.5 text-xs text-rose-500 font-semibold" />
        </div>

        <div>
            <label for="update_password_password_confirmation" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Confirmar Nueva Contraseña</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                placeholder="••••••••"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 text-slate-800 text-sm font-medium transition-all outline-none bg-slate-50/50 hover:bg-white focus:bg-white">
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-1.5 text-xs text-rose-500 font-semibold" />
        </div>

        <div class="flex items-center gap-4 pt-2">
            <button type="submit"
                class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-sm shadow-md shadow-indigo-500/25 hover:shadow-lg hover:shadow-indigo-500/35 transition-all duration-200 focus:outline-none cursor-pointer">
                Actualizar Contraseña
            </button>

            @if (session('status') === 'password-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)"
                    class="text-sm font-semibold text-emerald-600 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    ¡Contraseña actualizada correctamente!
                </p>
            @endif
        </div>
    </form>
</section>
