<div x-data @keydown.window="if (($event.ctrlKey || $event.metaKey) && $event.key.toLowerCase() === 'k') { $event.preventDefault(); $dispatch('open-modal', 'workspace-search'); }">
    <div class="workspace-toolbar">
        <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-slate-700 hover:text-emerald-800">{{ __('Mi espacio de trabajo') }}</a>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" class="workspace-search-trigger" @click="$dispatch('open-modal', 'workspace-search')" aria-label="{{ __('Buscar proyectos, actividades y empleados') }}">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5" stroke-width="2"/><path d="m16 16 4 4" stroke-width="2" stroke-linecap="round"/></svg>
                <span>{{ __('Buscar') }}</span><kbd class="hidden sm:inline rounded border border-slate-200 bg-white px-1.5 text-xs">Ctrl K</kbd>
            </button>
            <a href="{{ route('tasks.index', ['mine' => 1]) }}" class="workspace-button hidden sm:inline-flex">{{ __('Mis actividades') }}</a>
            @unless(auth()->user()->isEmployee()) <a href="{{ route('alerts.index') }}" class="workspace-button">{{ __('Alertas') }}</a> @endunless
        </div>
    </div>
    <x-ui.modal name="workspace-search" :label="__('Buscar en mi espacio')" max-width="2xl" focusable>
        <div x-data="workspaceSearch(@js(route('workspace.search')))" class="flex min-h-0 flex-col">
            <div class="flex items-center justify-between border-b border-slate-100 p-5">
                <h2 class="workspace-section-title">{{ __('Encuentra lo que necesitas') }}</h2>
                <button type="button" @click="$dispatch('close')" class="workspace-button" aria-label="{{ __('Cerrar búsqueda') }}">✕</button>
            </div>
            <div class="p-5">
                <label for="workspace-query" class="sr-only">{{ __('Nombre o código') }}</label>
                <input id="workspace-query" type="search" x-model="query" @input.debounce.300ms="search()" maxlength="100" placeholder="{{ __('Busca un proyecto, una actividad o una persona…') }}" class="w-full rounded-xl border-slate-300 focus:border-emerald-600 focus:ring-emerald-600">
                <p class="mt-2 text-xs text-slate-500">{{ __('Escribe al menos 2 caracteres. Solo verás resultados a los que tienes acceso.') }}</p>
            </div>
            <div class="max-h-[50dvh] overflow-y-auto px-5 pb-5" aria-live="polite" :aria-busy="loading">
                <p x-show="loading" class="py-6 text-center text-sm text-slate-600">{{ __('Buscando…') }}</p>
                <p x-show="error" x-text="error" class="py-4 text-sm text-rose-700" role="alert"></p>
                <p x-show="!loading && !error && query.trim().length >= 2 && results.length === 0" class="py-6 text-center text-sm text-slate-600">{{ __('No encontramos coincidencias. Prueba otro nombre o código.') }}</p>
                <template x-for="result in results" :key="result.url">
                    <a :href="result.url" class="mb-2 flex items-center justify-between gap-3 rounded-xl border border-slate-200 p-4 hover:border-emerald-300 hover:bg-emerald-50">
                        <span class="min-w-0"><span class="block break-words text-sm font-semibold text-slate-900" x-text="result.title"></span><span class="block text-xs text-slate-600" x-text="result.detail"></span></span>
                        <span class="shrink-0 rounded-lg bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600" x-text="result.type"></span>
                    </a>
                </template>
            </div>
        </div>
    </x-ui.modal>
</div>
