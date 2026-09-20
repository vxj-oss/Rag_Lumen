<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="$project->name" :subtitle="$project->code . ' · ' . ($project->client?->name ?? __('Sin cliente'))">
            <x-slot name="actions">
                <a href="{{ route('projects.export.one', $project) }}" class="inline-flex items-center gap-2 px-3 py-2 min-h-[44px] rounded-lg text-sm font-medium text-gray-600 bg-white ring-1 ring-gray-300 hover:bg-gray-50 transition">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2" /></svg>
                    <span class="hidden sm:inline">{{ __('Exportar PDF') }}</span>
                </a>
                @can('update', $project)
                <x-ui.secondary-button x-data @click="$dispatch('open-modal', 'edit-project')" class="min-h-[44px]">
                    {{ __('Editar') }}
                </x-ui.secondary-button>
                <a href="{{ route('projects.statuses.index', $project) }}" class="inline-flex items-center px-3 py-2 min-h-[44px] rounded-lg text-sm font-medium text-gray-600 bg-white ring-1 ring-gray-300 hover:bg-gray-50 transition">
                    {{ __('Estados') }}
                </a>
                @endcan
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div>
                <a href="{{ route('projects.index') }}" class="inline-flex items-center min-h-[44px] text-sm text-emerald-600 hover:text-emerald-800 font-medium">
                    {{ __('← Volver a proyectos') }}
                </a>
            </div>
            <x-ui.card padding="p-6 space-y-6">
                <div class="flex gap-2">
                    <x-ui.badge :color="$project->status->color()">{{ $project->status->label() }}</x-ui.badge>
                    <x-ui.badge :color="$project->priority->color()">{{ __('Prioridad') }}: {{ $project->priority->label() }}</x-ui.badge>
                    <x-ui.badge :color="$risk['level']->color()">{{ __('Riesgo') }}: {{ $risk['level']->label() }} ({{ $risk['score'] }})</x-ui.badge>
                </div>

                @if ($project->description)
                <p class="text-sm text-gray-700">{{ $project->description }}</p>
                @endif

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Tipo') }}</dt>
                        <dd class="text-sm text-gray-900">{{ $project->type->label() }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Cliente') }}</dt>
                        <dd class="text-sm text-gray-900">{{ $project->client?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Gerente') }}</dt>
                        <dd class="text-sm text-gray-900">{{ $project->manager?->fullName() ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Líder') }}</dt>
                        <dd class="text-sm text-gray-900">{{ $project->responsibleEmployee?->fullName() ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Presupuesto') }}</dt>
                        <dd class="text-sm text-gray-900">{{ $project->budget ? number_format((float) $project->budget, 2) : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Fecha de inicio') }}</dt>
                        <dd class="text-sm text-gray-900">{{ $project->start_date->format('d/m/Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Fecha fin estimada') }}</dt>
                        <dd class="text-sm text-gray-900">{{ $project->estimated_end_date->format('d/m/Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Fecha fin real') }}</dt>
                        <dd class="text-sm text-gray-900">{{ $project->actual_end_date?->format('d/m/Y') ?? '—' }}</dd>
                    </div>
                </dl>

                @if ($project->observations)
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('Observaciones') }}</dt>
                    <dd class="text-sm text-gray-900">{{ $project->observations }}</dd>
                </div>
                @endif
            </x-ui.card>

            <x-ui.card padding="p-6 space-y-4">
                <h3 class="font-semibold text-gray-800">{{ __('Estado del proyecto') }}</h3>
                <p class="text-xs text-gray-500 -mt-2">{{ __('Señales generadas automáticamente a partir de métricas y riesgo — sin intervención de IA.') }}</p>

                <div class="flex flex-wrap gap-2">
                    @foreach ($decision['signals'] as $signal)
                    <x-ui.badge :color="$signal->color()">{{ $signal->label() }}</x-ui.badge>
                    @endforeach
                </div>

                @if (count($decision['reasons']) > 0)
                <div>
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">{{ __('Motivos') }}</h4>
                    <ul class="list-disc list-inside text-sm text-gray-700 space-y-0.5">
                        @foreach ($decision['reasons'] as $reason)
                        <li>{{ $reason }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                @if (count($decision['recommended_actions']) > 0)
                <div>
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">{{ __('Acciones recomendadas') }}</h4>
                    <ul class="list-disc list-inside text-sm text-gray-700 space-y-0.5">
                        @foreach ($decision['recommended_actions'] as $action)
                        <li>{{ $action }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </x-ui.card>

            <x-ui.card padding="p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-gray-800">{{ __('Métricas') }}</h3>
                    @php
                    $trendLabel = match ($metrics['trend']) {
                    'accelerating' => __('Acelerando'),
                    'decelerating' => __('Desacelerando'),
                    'stable' => __('Estable'),
                    default => __('Sin datos suficientes'),
                    };
                    $trendColor = match ($metrics['trend']) {
                    'accelerating' => 'green',
                    'decelerating' => 'red',
                    'stable' => 'blue',
                    default => 'gray',
                    };
                    @endphp
                    <x-ui.badge :color="$trendColor">{{ __('Tendencia') }}: {{ $trendLabel }}</x-ui.badge>
                </div>

                <div>
                    <div class="flex items-center justify-between text-sm mb-1">
                        <span class="text-gray-500">{{ __('Avance real vs. esperado') }}</span>
                        <span class="font-medium {{ $metrics['progress_gap'] > 10 ? 'text-red-600' : ($metrics['progress_gap'] > 0 ? 'text-yellow-600' : 'text-emerald-600') }}">
                            {{ $metrics['real_progress'] }}% / {{ $metrics['expected_progress'] }}%
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2 relative">
                        <div class="bg-emerald-500 h-2 rounded-full" @style(["width: {$metrics['real_progress']}%"])></div>
                        <div class="absolute top-0 h-2 w-0.5 bg-gray-700" @style(["left: " . min(100, $metrics['expected_progress']) . " %"])></div>
                    </div>
                    @if ($metrics['progress_gap'] > 0)
                    <p class="text-xs text-gray-500 mt-1">{{ __('El proyecto va :gap puntos por debajo de lo esperado.', ['gap' => $metrics['progress_gap']]) }}</p>
                    @endif
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <x-ui.stat-card :label="__('Tareas totales')" :value="$metrics['total_tasks']" color="emerald" />
                    <x-ui.stat-card :label="__('Completadas')" :value="$metrics['completed_tasks']" color="emerald" />
                    <x-ui.stat-card :label="__('Bloqueadas')" :value="$metrics['blocked_tasks']" color="red" />
                    <x-ui.stat-card :label="__('Atrasadas')" :value="$metrics['overdue_tasks']" color="yellow" />
                </div>

                <dl class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm pt-2">
                    <div>
                        <dt class="text-gray-500">{{ __('Próximas a vencer (3 días)') }}</dt>
                        <dd class="font-medium text-gray-900">{{ $metrics['due_soon_tasks'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('Cumplimiento de fechas') }}</dt>
                        <dd class="font-medium text-gray-900">{{ $metrics['on_time_completion_rate'] !== null ? $metrics['on_time_completion_rate'].'%' : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">{{ __('Horas (estimadas / reales)') }}</dt>
                        <dd class="font-medium text-gray-900">{{ $metrics['estimated_hours'] }} / {{ $metrics['actual_hours'] }}</dd>
                    </div>
                </dl>
            </x-ui.card>

            <x-ui.card padding="p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-gray-800">{{ __('Análisis de riesgo') }}</h3>
                    <x-ui.badge :color="$risk['level']->color()">{{ $risk['score'] }}/100 — {{ $risk['level']->label() }}</x-ui.badge>
                </div>
                <p class="text-xs text-gray-500">{{ __('Cada componente aporta un puntaje de 0 a 100 según su peso configurado; el resultado final es determinístico y recalculable.') }}</p>

                <div class="space-y-3">
                    @foreach ([
                    ['label' => __('Atrasos'), 'value' => $risk['components']['delay_score'], 'weight' => '25%'],
                    ['label' => __('Bloqueos'), 'value' => $risk['components']['blocked_score'], 'weight' => '20%'],
                    ['label' => __('Brecha de avance'), 'value' => $risk['components']['progress_gap_score'], 'weight' => '25%'],
                    ['label' => __('Presión de fecha límite'), 'value' => $risk['components']['deadline_pressure_score'], 'weight' => '20%'],
                    ['label' => __('Carga del equipo'), 'value' => $risk['components']['workload_score'], 'weight' => '10%'],
                    ] as $component)
                    <div>
                        <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                            <span>{{ $component['label'] }} <span class="text-gray-400">({{ $component['weight'] }})</span></span>
                            <span class="font-medium text-gray-700">{{ $component['value'] }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-1.5">
                            <div class="bg-emerald-500 h-1.5 rounded-full" @style(["width: " . min(100, $component['value']) . " %"])></div>
                        </div>
                    </div>
                    @endforeach

                    <div class="flex items-center justify-between text-xs pt-1 border-t border-gray-100">
                        <span class="text-gray-500">{{ __('Ajuste por prioridad') }} ({{ $project->priority->label() }})</span>
                        <span class="font-medium {{ $risk['components']['priority_modifier'] >= 0 ? 'text-red-600' : 'text-emerald-600' }}">
                            {{ $risk['components']['priority_modifier'] >= 0 ? '+' : '' }}{{ $risk['components']['priority_modifier'] }}
                        </span>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card padding="p-6 space-y-4">
                <h3 class="font-semibold text-gray-800">{{ __('Miembros del equipo') }}</h3>

                <div class="overflow-x-auto -mx-1 px-1">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Empleado') }}</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Rol') }}</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Desde') }}</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Estado') }}</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($project->members as $member)
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-900">{{ $member->fullName() }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ $member->pivot->role_in_project }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ $member->pivot->assigned_at->format('d/m/Y') }}</td>
                            <td class="px-4 py-2 text-sm">
                                <x-ui.badge :color="$member->pivot->status === 'active' ? 'green' : 'gray'">
                                    {{ $member->pivot->status === 'active' ? __('Activo') : __('Inactivo') }}
                                </x-ui.badge>
                            </td>
                            <td class="px-4 py-2 text-right">
                                @can('update', $project)
                                @if ($member->pivot->status === 'active')
                                <form action="{{ route('projects.members.destroy', [$project, $member->pivot->id]) }}" method="POST" class="inline" @submit.prevent="if (confirm(@js(__('¿Quitar a este miembro del proyecto?')))) $el.submit()">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline text-sm">{{ __('Quitar') }}</button>
                                </form>
                                @endif
                                @endcan
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-4 py-2 text-sm text-gray-500 text-center">{{ __('Sin miembros asignados.') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>

                @can('update', $project)
                @if ($availableEmployees->isNotEmpty())
                <form action="{{ route('projects.members.store', $project) }}" method="POST" class="flex flex-wrap items-end gap-3 pt-4 border-t border-gray-100">
                    @csrf
                    <div>
                        <x-forms.input-label for="employee_id" :value="__('Empleado')" />
                        <x-forms.select id="employee_id" name="employee_id" class="mt-1"
                            :options="$availableEmployees->mapWithKeys(fn ($e) => [$e->id => $e->fullName()])" />
                    </div>
                    <div>
                        <x-forms.input-label for="role_in_project" :value="__('Rol en el proyecto')" />
                        <x-forms.text-input id="role_in_project" name="role_in_project" type="text" class="mt-1"
                            placeholder="{{ __('ej. lead, developer, designer') }}" required />
                    </div>
                    <div>
                        <x-forms.input-label for="assigned_at" :value="__('Fecha de asignación')" />
                        <x-forms.text-input id="assigned_at" name="assigned_at" type="date" class="mt-1"
                            value="{{ now()->toDateString() }}" required />
                    </div>
                    <x-ui.primary-button type="submit">{{ __('Agregar') }}</x-ui.primary-button>
                </form>
                @error('employee_id') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                @error('role_in_project') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                @error('assigned_at') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                @endif
                @endcan
            </x-ui.card>

            <x-ui.card padding="p-6 space-y-4">
                <h3 class="font-semibold text-gray-800">{{ __('Áreas participantes') }}</h3>

                @forelse ($areaProgress as $row)
                <div>
                    <div class="flex items-center justify-between text-sm mb-1">
                        <span class="font-medium text-gray-800">{{ $row['area']->name }}</span>
                        <span class="text-gray-500">{{ $row['completed'] }}/{{ $row['total'] }} · {{ $row['percent'] }}% ({{ __('ponderado') }} {{ $row['weighted'] }}%)</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-emerald-500 h-2 rounded-full" @style(["width: {$row['percent']}%"])></div>
                    </div>
                </div>
                @empty
                <p class="text-sm text-gray-500">{{ __('Este proyecto aún no tiene áreas asignadas.') }}</p>
                @endforelse
            </x-ui.card>
        </div>
    </div>

    @can('update', $project)
    <x-ui.form-modal name="edit-project" :title="__('Editar proyecto')" :subtitle="$project->code"
        :action="route('projects.update', $project)" method="PUT" max-width="3xl">
        @include('projects.html.partials.form')
    </x-ui.form-modal>

    @if ($errors->any())
    <div x-data x-init="$dispatch('open-modal', 'edit-project')"></div>
    @endif
    @endcan
</x-app-layout>