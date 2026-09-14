<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf
        <div>
            <x-forms.input-label for="name" :value="__('Name')" />
            <x-forms.text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-forms.input-error :messages="$errors->get('name')" class="mt-2" />
        </div>
        <div class="mt-4">
            <x-forms.input-label for="email" :value="__('Email')" />
            <x-forms.text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-forms.input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div class="mt-4">
            <x-forms.input-label for="password" :value="__('Password')" />

            <x-forms.text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-forms.input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <div class="mt-4">
            <x-forms.input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-forms.text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-forms.input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-ui.primary-button class="ms-4">
                {{ __('Register') }}
            </x-ui.primary-button>
        </div>
    </form>
</x-guest-layout>
