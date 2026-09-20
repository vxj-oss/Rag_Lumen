<x-auth-layout :title="__('Crear cuenta')" :subtitle="__('Regístrate para empezar a gestionar tus proyectos')">
    <form method="POST" action="{{ route('register') }}" x-data="{ sending: false }" @submit="sending = true">
        @csrf
        <div>
            <x-forms.input-label for="name" :value="__('Nombre')" />
            <x-forms.text-input id="name" class="block mt-1 w-full text-base sm:text-sm" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" maxlength="255" />
            <x-forms.input-error :messages="$errors->get('name')" class="mt-2" />
        </div>
        <div class="mt-4">
            <x-forms.input-label for="email" :value="__('Correo electrónico')" />
            <x-forms.text-input id="email" class="block mt-1 w-full text-base sm:text-sm" type="email" name="email" :value="old('email')" required autocomplete="email" maxlength="255" />
            <x-forms.input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div class="mt-4">
            @include('auth.html.partials.password-field', [
                'id' => 'password',
                'label' => __('Contraseña'),
                'autocomplete' => 'new-password',
            ])
        </div>
        <div class="mt-4">
            @include('auth.html.partials.password-field', [
                'id' => 'password_confirmation',
                'label' => __('Confirmar contraseña'),
                'autocomplete' => 'new-password',
            ])
        </div>

        <div class="mt-4">
            <x-ui.primary-button class="w-full justify-center min-h-[44px]" x-bind:disabled="sending">
                <svg x-show="sending" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span x-text="sending ? @js(__('Creando cuenta…')) : @js(__('Registrarse'))">{{ __('Registrarse') }}</span>
            </x-ui.primary-button>
        </div>

        <div class="mt-4 text-center">
            <a class="inline-block min-h-[44px] px-2 py-2.5 text-sm text-emerald-600 hover:text-emerald-800 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500" href="{{ route('login') }}">
                {{ __('¿Ya tienes cuenta? Inicia sesión') }}
            </a>
        </div>
    </form>
</x-auth-layout>
