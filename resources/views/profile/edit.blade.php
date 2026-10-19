<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-xl">
                <div class="max-w-xl">
                    @unless(auth()->user()->isEmployee()) @include('profile.partials.update-profile-information-form') @else <p>{{ auth()->user()->name }} · {{ auth()->user()->email }}</p> @endunless
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-xl">
                <div class="max-w-xl">
                    @unless(auth()->user()->isEmployee()) @include('profile.partials.update-password-form') @else <p class="text-sm text-slate-600">Tu gerente, líder o administrador gestiona tu contraseña permanente.</p> @endunless
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-xl">
                <div class="max-w-xl">
                    @unless(auth()->user()->isEmployee()) @include('profile.partials.delete-user-form') @endunless
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
