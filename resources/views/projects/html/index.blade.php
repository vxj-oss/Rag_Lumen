<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="__('Proyectos')" :subtitle="__('Todos los proyectos de la empresa')">
            <x-slot name="actions">
                <a href="{{ route('projects.export') }}" class="inline-flex items-center gap-2 px-3 py-2 min-h-[44px] rounded-lg text-sm font-medium text-gray-600 bg-white ring-1 ring-gray-300 hover:bg-gray-50 transition">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2" /></svg>
                    <span class="hidden sm:inline">{{ __('Exportar CSV') }}</span>
                </a>
                @can('create', \App\Models\Project::class)
                    <x-ui.primary-button x-data @click="$dispatch('open-modal', 'create-project')" class="min-h-[44px]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        {{ __('Nuevo proyecto') }}
                    </x-ui.primary-button>
                @endcan
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <x-ui.stat-card :label="__('Proyectos totales')" :value="$totalCount" color="emerald"
                    icon='<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-19.5 0v6a2.25 2.25 0 002.25 2.25h15a2.25 2.25 0 002.25-2.25v-6m-19.5 0h19.5" />' />
                <x-ui.stat-card :label="__('En progreso')" :value="$inProgressCount" color="blue"
                    icon='<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />' />
                <x-ui.stat-card :label="__('Bloqueados')" :value="$blockedCount" color="red"
                    icon='<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />' />
                <x-ui.stat-card :label="__('Riesgo alto o crítico')" :value="$highRiskCount" color="red"
                    icon='<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />' />
            </div>

            <x-ui.card padding="p-4">
                <form method="GET" action="{{ route('projects.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Buscar') }}</label>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Nombre o código...') }}"
                               class="w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Estado') }}</label>
                        <select name="status" class="w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm text-sm">
                            <option value="">{{ __('Todos') }}</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" {{ ($filters['status'] ?? '') === $status->value ? 'selected' : '' }}>{{ $status->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Prioridad') }}</label>
                        <select name="priority" class="w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm text-sm">
                            <option value="">{{ __('Todas') }}</option>
                            @foreach ($priorities as $priority)
                                <option value="{{ $priority->value }}" {{ ($filters['priority'] ?? '') === $priority->value ? 'selected' : '' }}>{{ $priority->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Cliente') }}</label>
                        <select name="client_id" class="w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm text-sm">
                            <option value="">{{ __('Todos') }}</option>
                            @foreach ($clients as $client)
                                <option value="{{ $client->id }}" {{ (string) ($filters['client_id'] ?? '') === (string) $client->id ? 'selected' : '' }}>{{ $client->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Área') }}</label>
                        <select name="area_id" class="w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm text-sm">
                            <option value="">{{ __('Todas') }}</option>
                            @foreach ($areas as $area)
                                <option value="{{ $area->id }}" {{ (string) ($filters['area_id'] ?? '') === (string) $area->id ? 'selected' : '' }}>{{ $area->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-4 flex items-center gap-3">
                        <button type="submit" class="px-3 py-2 rounded-lg text-sm font-medium bg-emerald-600 text-white hover:bg-emerald-700 transition">{{ __('Filtrar') }}</button>
                        @if (array_filter($filters ?? []))
                            <a href="{{ route('projects.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('Limpiar filtros') }}</a>
                        @endif
                    </div>
                </form>
            </x-ui.card>

            <x-ui.card padding="p-0">
                <div class="hidden lg:block overflow-x-auto">
                    <table class="w-full divide-y divide-gray-200 table-fixed">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider border-b-2 border-emerald-500/30 w-[20%]">{{ __('Proyecto') }}</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider border-b-2 border-emerald-500/30 w-[14%]">{{ __('Cliente') }}</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider border-b-2 border-emerald-500/30 w-[13%]">{{ __('Responsable') }}</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider border-b-2 border-emerald-500/30 w-[15%]">{{ __('Estado') }}</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider border-b-2 border-emerald-500/30 w-[10%]">{{ __('Prioridad') }}</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider border-b-2 border-emerald-500/30 w-[16%]">{{ __('Riesgo') }}</th>
                                <th class="px-3 py-3 border-b-2 border-emerald-500/30 w-[12%]"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @forelse ($projects as $project)
                                <tr class="hover:bg-emerald-50/40 transition">
                                    <td class="px-3 py-3 truncate">
                                        <div class="text-sm font-medium text-gray-900 truncate" title="{{ $project->nombre }}">{{ $project->nombre }}</div>
                                        <div class="text-xs text-gray-400">{{ $project->codigo }}</div>
                                    </td>
                                    <td class="px-3 py-3 text-sm text-gray-500 truncate" title="{{ $project->client?->nombre }}">{{ $project->client?->nombre ?? '—' }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-500 truncate">{{ $project->responsibleEmployee?->fullName() ?? '—' }}</td>
                                    <td class="px-3 py-3">
                                        <x-ui.badge :color="$project->estado->color()">{{ $project->estado->label() }}</x-ui.badge>
                                    </td>
                                    <td class="px-3 py-3">
                                        <x-ui.badge :color="$project->prioridad->color()">{{ $project->prioridad->label() }}</x-ui.badge>
                                    </td>
                                    <td class="px-3 py-3">
                                        <x-ui.badge :color="$risks[$project->id]['level']->color()">
                                            {{ $risks[$project->id]['level']->label() }} ({{ $risks[$project->id]['score'] }})
                                        </x-ui.badge>
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap text-right text-sm">
                                        <div class="flex items-center justify-end gap-1">
                                            <a href="{{ route('projects.show', $project) }}" title="{{ __('Ver') }}" class="p-1.5 rounded-lg text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                            </a>
                                            @can('update', $project)
                                                <button x-data @click="$dispatch('open-modal', 'edit-project-{{ $project->id }}')" title="{{ __('Editar') }}" class="p-1.5 rounded-lg text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 transition">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" /></svg>
                                                </button>
                                            @endcan
                                            @can('delete', $project)
                                                <form action="{{ route('projects.destroy', $project) }}" method="POST" @submit.prevent="if (confirm(@js(__('¿Eliminar este proyecto?')))) $el.submit()">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" title="{{ __('Eliminar') }}" class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>

                                @can('update', $project)
                                    <x-ui.form-modal :name="'edit-project-'.$project->id" :title="__('Editar proyecto')"
                                        :subtitle="$project->codigo" :action="route('projects.update', $project)" method="PUT" max-width="3xl">
                                        <x-slot name="hidden">
                                            <input type="hidden" name="_edit_id" value="{{ $project->id }}">
                                        </x-slot>
                                        @include('projects.html.partials.form')
                                    </x-ui.form-modal>
                                @endcan
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-16 text-center">
                                        <svg class="w-10 h-10 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-19.5 0v6a2.25 2.25 0 002.25 2.25h15a2.25 2.25 0 002.25-2.25v-6m-19.5 0h19.5" /></svg>
                                        <p class="mt-2 text-sm text-gray-500">{{ __('Todavía no hay proyectos registrados.') }}</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="lg:hidden divide-y divide-gray-100">
                    @forelse ($projects as $project)
                        <div class="p-4 space-y-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $project->nombre }}</p>
                                    <p class="text-xs text-gray-400">{{ $project->codigo }}</p>
                                </div>
                                <x-ui.badge :color="$project->estado->color()">{{ $project->estado->label() }}</x-ui.badge>
                            </div>
                            <div class="flex flex-wrap gap-1.5">
                                <x-ui.badge :color="$project->prioridad->color()">{{ $project->prioridad->label() }}</x-ui.badge>
                                <x-ui.badge :color="$risks[$project->id]['level']->color()">
                                    {{ $risks[$project->id]['level']->label() }} ({{ $risks[$project->id]['score'] }})
                                </x-ui.badge>
                            </div>
                            <dl class="grid grid-cols-2 gap-2 text-xs">
                                <div>
                                    <dt class="font-medium text-gray-400">{{ __('Cliente') }}</dt>
                                    <dd class="text-gray-700 truncate">{{ $project->client?->nombre ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-400">{{ __('Responsable') }}</dt>
                                    <dd class="text-gray-700 truncate">{{ $project->responsibleEmployee?->fullName() ?? '—' }}</dd>
                                </div>
                            </dl>
                            <div class="flex items-center gap-4 pt-1 text-sm border-t border-gray-100 mt-1 pt-3">
                                <a href="{{ route('projects.show', $project) }}" class="text-gray-500 hover:text-emerald-600 transition">{{ __('Ver') }}</a>
                                @can('update', $project)
                                    <button x-data @click="$dispatch('open-modal', 'edit-project-{{ $project->id }}')" class="text-emerald-600 hover:text-emerald-800 font-medium transition">{{ __('Editar') }}</button>
                                @endcan
                                @can('delete', $project)
                                    <form action="{{ route('projects.destroy', $project) }}" method="POST" class="inline" @submit.prevent="if (confirm(@js(__('¿Eliminar este proyecto?')))) $el.submit()">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 font-medium transition">{{ __('Eliminar') }}</button>
                                    </form>
                                @endcan
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-16 text-center">
                            <svg class="w-10 h-10 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-19.5 0v6a2.25 2.25 0 002.25 2.25h15a2.25 2.25 0 002.25-2.25v-6m-19.5 0h19.5" /></svg>
                            <p class="mt-2 text-sm text-gray-500">{{ __('Todavía no hay proyectos registrados.') }}</p>
                        </div>
                    @endforelse
                </div>
            </x-ui.card>

            {{ $projects->links() }}
        </div>
    </div>

    @can('create', \App\Models\Project::class)
        <x-ui.form-modal name="create-project" :title="__('Nuevo proyecto')" :subtitle="__('Define un nuevo proyecto para la empresa')"
            :action="route('projects.store')" max-width="3xl" submit-label="{{ __('Crear proyecto') }}">
            <x-slot name="hidden">
                <input type="hidden" name="_form" value="create">
            </x-slot>
            @php
                $project = null;
            @endphp
            @include('projects.html.partials.form')
        </x-ui.form-modal>
    @endcan

    @if ($errors->any())
        @php
            $modalToOpen = old('_edit_id') ? 'edit-project-'.old('_edit_id') : (old('_form') === 'create' ? 'create-project' : null);
        @endphp
        @if ($modalToOpen)
            <div x-data x-init="$dispatch('open-modal', @js($modalToOpen))"></div>
        @endif
    @endif
</x-app-layout>