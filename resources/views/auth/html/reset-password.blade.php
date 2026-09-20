<x-auth-layout :title="__('Nueva contraseña')" :subtitle="__('Elige una contraseña segura para tu cuenta')">
    <form method="POST" action="{{ route('password.store') }}" x-data="{ sending: false }" @submit="sending = true">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <div>
            <x-forms.input-label for="email" :value="__('Correo electrónico')" />
            <x-forms.text-input id="email" class="block mt-1 w-full text-base sm:text-sm" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="email" maxlength="255" />
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
                <span x-text="sending ? @js(__('Guardando…')) : @js(__('Restablecer contraseña'))">{{ __('Restablecer contraseña') }}</span>
            </x-ui.primary-button>
        </div>
    </form>
</x-auth-layout>
