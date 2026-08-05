<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-black text-slate-800 tracking-tight">
                    Mi Perfil
                </h2>
                <p class="text-sm text-slate-500 font-medium mt-0.5">Gestiona la información de tu cuenta y la configuración de seguridad</p>
            </div>
            <div class="px-4 py-2 bg-indigo-50 border border-indigo-100 rounded-xl text-indigo-700 text-xs font-bold flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-indigo-600 animate-pulse"></span>
                {{ Auth::user()->name }}
            </div>
        </div>
    </x-slot>

    <div class="py-6 space-y-6">
        <!-- Información Personal -->
        <div class="p-6 sm:p-8 bg-white border border-slate-200/80 shadow-xs sm:rounded-2xl">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <!-- Seguridad y Contraseña -->
        <div class="p-6 sm:p-8 bg-white border border-slate-200/80 shadow-xs sm:rounded-2xl">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <!-- Zona de Peligro: Eliminar Cuenta -->
        <div class="p-6 sm:p-8 bg-rose-50/50 border border-rose-100 shadow-xs sm:rounded-2xl">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>

    <!-- SweetAlert2 Scripts for Profile Status -->
    @if (session('status') === 'profile-updated')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    title: '¡Perfil Actualizado!',
                    text: 'Tu información personal se ha guardado correctamente.',
                    icon: 'success',
                    confirmButtonColor: '#4f46e5',
                    timer: 3000,
                    timerProgressBar: true
                });
            });
        </script>
    @endif

    @if (session('status') === 'password-updated')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    title: '¡Contraseña Actualizada!',
                    text: 'Tu contraseña se ha cambiado de forma segura.',
                    icon: 'success',
                    confirmButtonColor: '#4f46e5',
                    timer: 3000,
                    timerProgressBar: true
                });
            });
        </script>
    @endif
</x-app-layout>
