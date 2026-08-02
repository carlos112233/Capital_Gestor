<section>
    <header class="mb-6">
        <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            Información del Perfil
        </h2>
        <p class="mt-1 text-sm text-slate-500 font-medium">
            Actualiza el nombre, número de teléfono y dirección de correo electrónico asociada a tu cuenta.
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-5">
        @csrf
        @method('patch')

        <div>
            <label for="name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Nombre Completo</label>
            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 text-slate-800 text-sm font-medium transition-all outline-none bg-slate-50/50 hover:bg-white focus:bg-white">
            <x-input-error class="mt-1.5 text-xs text-rose-500 font-semibold" :messages="$errors->get('name')" />
        </div>

        <div>
            <label for="telefono" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Teléfono / WhatsApp</label>
            <input id="telefono" name="telefono" type="text" value="{{ old('telefono', $user->telefono) }}" required autocomplete="tel"
                placeholder="Ej. 5512345678"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 text-slate-800 text-sm font-medium transition-all outline-none bg-slate-50/50 hover:bg-white focus:bg-white">
            <x-input-error class="mt-1.5 text-xs text-rose-500 font-semibold" :messages="$errors->get('telefono')" />
        </div>

        <div>
            <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Correo Electrónico</label>
            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="username"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 text-slate-800 text-sm font-medium transition-all outline-none bg-slate-50/50 hover:bg-white focus:bg-white">
            <x-input-error class="mt-1.5 text-xs text-rose-500 font-semibold" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-2">
                    <p class="text-sm text-slate-700">
                        Tu correo electrónico no está verificado.
                        <button form="send-verification" class="underline text-sm text-indigo-600 hover:text-indigo-800 font-bold">
                            Haz clic aquí para reenviar el correo de verificación.
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-semibold text-sm text-emerald-600">
                            Se ha enviado un nuevo enlace de verificación a tu correo.
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4 pt-2">
            <button type="submit"
                class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-sm shadow-md shadow-indigo-500/25 hover:shadow-lg hover:shadow-indigo-500/35 transition-all duration-200 focus:outline-none cursor-pointer">
                Guardar Cambios
            </button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)"
                    class="text-sm font-semibold text-emerald-600 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    ¡Guardado correctamente!
                </p>
            @endif
        </div>
    </form>
</section>
