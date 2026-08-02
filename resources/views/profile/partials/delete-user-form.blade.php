<section class="space-y-6">
    <header>
        <h2 class="text-lg font-bold text-rose-700 flex items-center gap-2">
            <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            Eliminar Cuenta
        </h2>

        <p class="mt-1 text-sm text-slate-600">
            Una vez que tu cuenta sea eliminada, todos sus datos e historial serán borrados de manera permanente. Antes de continuar, asegúrate de respaldar cualquier información importante.
        </p>
    </header>

    <button type="button"
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-sm shadow-md shadow-rose-600/20 transition-all duration-200 focus:outline-none cursor-pointer">
        Eliminar mi cuenta
    </button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-bold text-slate-800">
                ¿Estás seguro de que deseas eliminar tu cuenta?
            </h2>

            <p class="mt-2 text-sm text-slate-600">
                Por favor, ingresa tu contraseña para confirmar que deseas eliminar tu cuenta permanentemente.
            </p>

            <div class="mt-4">
                <label for="password" class="sr-only">Contraseña</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 text-slate-800 text-sm font-medium transition-all outline-none"
                    placeholder="Tu contraseña actual"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2 text-xs text-rose-600 font-bold" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" x-on:click="$dispatch('close')"
                    class="px-5 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-sm cursor-pointer">
                    Cancelar
                </button>

                <button type="submit"
                    class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-sm shadow-md cursor-pointer">
                    Sí, eliminar cuenta
                </button>
            </div>
        </form>
    </x-modal>
</section>
