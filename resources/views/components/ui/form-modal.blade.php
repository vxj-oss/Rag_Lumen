@props(['name', 'title', 'subtitle' => null, 'action', 'method' => 'POST', 'maxWidth' => '2xl', 'submitLabel' => null, 'multipart' => false])

<x-ui.modal :name="$name" :max-width="$maxWidth" focusable>
    <form action="{{ $action }}" method="POST" class="flex flex-col max-h-[85vh]" @if ($multipart) enctype="multipart/form-data" @endif>
        @csrf
        @if (strtoupper($method) !== 'POST')
            @method($method)
        @endif
        {{ $hidden ?? '' }}

        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 shrink-0 bg-gradient-to-r from-emerald-50 via-white to-white rounded-t-2xl">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 tracking-tight">{{ $title }}</h3>
                @if ($subtitle)
                    <p class="text-sm text-gray-500">{{ $subtitle }}</p>
                @endif
            </div>
            <button type="button" @click="$dispatch('close')" class="text-gray-400 hover:text-gray-600 hover:rotate-90 rounded-full p-1.5 hover:bg-gray-100 transition-all duration-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="px-6 py-5 overflow-y-auto space-y-5">
            {{ $slot }}
        </div>

        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-3 shrink-0 rounded-b-2xl">
            <x-ui.secondary-button type="button" @click="$dispatch('close')">{{ __('Cancelar') }}</x-ui.secondary-button>
            <x-ui.primary-button type="submit">{{ $submitLabel ?? __('Guardar') }}</x-ui.primary-button>
        </div>
    </form>
</x-ui.modal>
