<x-auth-layout :title="__('Confirmar contraseña')" :subtitle="__('Zona segura: confirma tu contraseña para continuar')">
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Esta es una zona segura de la aplicación. Confirma tu contraseña antes de continuar.') }}
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" x-data="{ sending: false }" @submit="sending = true">
        @csrf
        <div>
            @include('auth.html.partials.password-field', [
                'id' => 'password',
                'label' => __('Contraseña'),
                'autocomplete' => 'current-password',
                'autofocus' => true,
            ])
        </div>

        <div class="mt-4">
            <x-ui.primary-button class="w-full justify-center min-h-[44px]" x-bind:disabled="sending">
                <svg x-show="sending" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span x-text="sending ? @js(__('Confirmando…')) : @js(__('Confirmar'))">{{ __('Confirmar') }}</span>
            </x-ui.primary-button>
        </div>
    </form>
</x-auth-layout>
