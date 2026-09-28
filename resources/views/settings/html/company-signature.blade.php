<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="__('Firma del Gerente General')" :subtitle="__('Se usa automáticamente en los convenios y documentos generados')" />
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-ui.card padding="p-6 space-y-4">
                @if ($firma)
                    <div>
                        <p class="text-xs font-medium text-gray-500 mb-2">{{ __('Firma actual') }}</p>
                        <img src="{{ $firma }}" alt="{{ __('Firma del Gerente General') }}" class="h-24 border border-gray-200 rounded-lg bg-white p-2">
                    </div>
                @else
                    <p class="text-sm text-gray-500">{{ __('Todavía no se ha subido una firma. Los convenios se generarán con ese espacio en blanco hasta que la subas.') }}</p>
                @endif

                <form method="POST" action="{{ route('settings.company-signature.update') }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <div>
                        <x-forms.input-label for="firma" :value="__('Subir nueva firma (PNG o JPG, fondo blanco o transparente)')" />
                        <input id="firma" name="firma" type="file" accept=".png,.jpg,.jpeg" required
                               class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:bg-emerald-50 file:text-emerald-700 file:text-sm file:font-medium hover:file:bg-emerald-100">
                        <x-forms.input-error :messages="$errors->get('firma')" class="mt-2" />
                    </div>
                    <div class="flex items-center gap-3">
                        <x-ui.primary-button type="submit">{{ __('Guardar firma') }}</x-ui.primary-button>
                        @if ($firma)
                            <form method="POST" action="{{ route('settings.company-signature.destroy') }}"
                                  @submit.prevent="if (confirm(@js(__('¿Quitar la firma actual?')))) $el.submit()" x-data>
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm text-red-600 hover:text-red-800 font-medium">{{ __('Quitar firma') }}</button>
                            </form>
                        @endif
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
