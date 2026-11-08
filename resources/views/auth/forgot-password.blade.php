<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('¿Olvidaste tu contraseña? No te preocupes. Ingresa tu correo electrónico o tu número de teléfono registrado y te enviaremos una contraseña temporal a tu WhatsApp para que recuperes el acceso de inmediato.') }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address or Phone -->
        <div>
            <x-input-label for="email" :value="__('Correo Electrónico o Teléfono')" />
            <x-text-input id="email" class="block mt-1 w-full" type="text" name="email" :value="old('email')" placeholder="ejemplo@correo.com o 5512345678" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('Volver a Iniciar Sesión') }}
            </a>

            <x-primary-button>
                {{ __('Enviar Contraseña por WhatsApp') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
