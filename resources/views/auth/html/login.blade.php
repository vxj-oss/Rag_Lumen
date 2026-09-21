<x-auth-layout :title="__('Iniciar sesión')" :subtitle="__('Accede a tus proyectos, tareas y asistente IA')">
    <form method="POST" action="{{ route('login') }}" x-data="{ sending: false }" @submit="sending = true" class="space-y-5">
        @csrf

        <div>
            <x-forms.input-label for="email" :value="__('Correo electrónico')" class="text-sm font-medium text-gray-700 mb-1" />
            <x-forms.text-input id="email" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500 h-11 px-3 text-sm" 
                type="email" name="email" :value="old('email')" required autofocus autocomplete="email" maxlength="255" />
            <x-forms.input-error :messages="$errors->get('email')" class="mt-1.5 text-xs text-red-600" />
        </div>

        <div>
            @include('auth.html.partials.password-field', [
                'id' => 'password',
                'label' => __('Contraseña'),
                'autocomplete' => 'current-password',
                'autofocus' => false,
            ])
        </div>

        <div class="flex items-center justify-between text-sm">
            <label for="remember_me" class="inline-flex items-center cursor-pointer select-none">
                <input id="remember_me" type="checkbox" class="w-4 h-4 rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500" name="remember">
                <span class="ms-2 text-gray-600">{{ __('Recordarme') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="font-medium text-emerald-600 hover:text-emerald-700" href="{{ route('password.request') }}">
                    {{ __('¿Olvidaste tu contraseña?') }}
                </a>
            @endif
        </div>

        <div class="pt-1">
            <x-ui.primary-button class="w-full justify-center h-11 rounded-lg text-sm font-semibold bg-emerald-600 hover:bg-emerald-700 shadow-sm transition" x-bind:disabled="sending">
                <svg x-show="sending" x-cloak class="w-4 h-4 animate-spin -ml-1 mr-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span x-text="sending ? @js(__('Ingresando…')) : @js(__('Ingresar'))">{{ __('Ingresar') }}</span>
            </x-ui.primary-button>
        </div>
    </form>
</x-auth-layout>