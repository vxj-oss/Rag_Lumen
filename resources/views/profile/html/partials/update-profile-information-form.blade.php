<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-forms.input-label for="name" :value="__('Name')" />
            <x-forms.text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-forms.input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-forms.input-label for="email" :value="__('Email')" />
            <x-forms.text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-forms.input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div>
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="notify_by_email" value="1" {{ old('notify_by_email', $user->notify_by_email) ? 'checked' : '' }}
                       class="rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500">
                <span class="text-sm text-gray-700">{{ __('Recibir alertas también por correo electrónico') }}</span>
            </label>
            <p class="text-xs text-gray-400 mt-1">{{ __('Las alertas siempre quedan registradas dentro del sistema; esto solo controla el correo adicional.') }}</p>
        </div>

        <div class="flex items-center gap-4">
            <x-ui.primary-button>{{ __('Save') }}</x-ui.primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
