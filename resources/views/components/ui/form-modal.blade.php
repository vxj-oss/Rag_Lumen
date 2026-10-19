@props(['name', 'title', 'subtitle' => null, 'action', 'method' => 'POST', 'maxWidth' => '2xl', 'submitLabel' => null, 'multipart' => false])

<x-ui.modal :name="$name" :max-width="$maxWidth" :label="$title" focusable>
    <form action="{{ $action }}" method="POST" data-submit-feedback class="flex flex-col min-h-0 flex-1" @if ($multipart) enctype="multipart/form-data" @endif>
        @csrf
        @if (strtoupper($method) !== 'POST')
            @method($method)
        @endif
        {{ $hidden ?? '' }}

        <div class="flex items-center justify-between px-6 py-3 border-b border-slate-100 shrink-0 bg-gradient-to-r from-emerald-50 via-white to-white rounded-t-2xl">
            <div>
                <h3 class="text-base font-semibold text-slate-900 tracking-tight">{{ $title }}</h3>
                @if ($subtitle)
                    <p class="text-xs text-slate-500">{{ $subtitle }}</p>
                @endif
            </div>
            <button type="button" @click="$dispatch('close')" aria-label="{{ __('Cerrar') }}" class="text-slate-500 hover:text-slate-600 hover:rotate-90 rounded-full w-9 h-9 inline-flex items-center justify-center hover:bg-slate-100 transition-all duration-200 shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="modal-compact-form px-6 py-3 overflow-y-auto min-h-0 space-y-3">
            @if ($errors->any())
                <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800">
                    <p class="font-semibold">{{ __('Revisa los campos antes de guardar.') }}</p>
                    <ul class="mt-1 list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            {{ $slot }}
        </div>

        <div class="px-6 py-3 border-t border-slate-100 bg-slate-50 flex justify-end gap-3 shrink-0 rounded-b-2xl">
            <x-ui.secondary-button type="button" @click="$dispatch('close')">{{ __('Cancelar') }}</x-ui.secondary-button>
            <x-ui.primary-button type="submit">{{ $submitLabel ?? __('Guardar') }}</x-ui.primary-button>
        </div>
    </form>
</x-ui.modal>
