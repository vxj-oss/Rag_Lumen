<x-auth-layout :title="__('Verifica tu correo')" :subtitle="__('Confirma tu dirección para continuar')">
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Gracias por registrarte. Antes de empezar, verifica tu correo con el enlace que te acabamos de enviar. Si no lo recibiste, te enviamos otro con gusto.') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600" role="status">
            {{ __('Se envió un nuevo enlace de verificación a tu correo.') }}
        </div>
    @endif

    <div class="mt-4 flex flex-col gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-ui.primary-button class="w-full justify-center min-h-[44px]">
                {{ __('Reenviar correo de verificación') }}
            </x-ui.primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full min-h-[44px] text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                {{ __('Cerrar sesión') }}
            </button>
        </form>
    </div>
</x-auth-layout>
