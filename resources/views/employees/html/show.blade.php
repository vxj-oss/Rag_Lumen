<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="$employee->fullName()" :subtitle="$employee->position ?? $employee->specialty->label()">
            @can('update', $employee)
                <x-slot name="actions">
                    <x-ui.secondary-button x-data @click="$dispatch('open-modal', 'edit-employee')" class="min-h-[44px]">
                        {{ __('Editar') }}
                    </x-ui.secondary-button>
                </x-slot>
            @endcan
        </x-ui.page-header>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div>
                <a href="{{ route('employees.index') }}" class="inline-flex items-center min-h-[44px] text-sm text-emerald-600 hover:text-emerald-800 font-medium">
                    {{ __('← Volver a empleados') }}
                </a>
            </div>
            <x-ui.card>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Email') }}</dt>
                        <dd class="text-sm text-gray-900">{{ $employee->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Teléfono') }}</dt>
                        <dd class="text-sm text-gray-900">{{ $employee->phone ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Cargo') }}</dt>
                        <dd class="text-sm text-gray-900">{{ $employee->position ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Especialidad') }}</dt>
                        <dd class="text-sm text-gray-900">{{ $employee->specialty->label() }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Área') }}</dt>
                        <dd class="text-sm text-gray-900">{{ $employee->area?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Estado') }}</dt>
                        <dd class="text-sm text-gray-900">
                            <x-ui.badge :color="$employee->status->value === 'active' ? 'green' : ($employee->status->value === 'on_leave' ? 'yellow' : 'gray')">
                                {{ $employee->status->label() }}
                            </x-ui.badge>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Fecha de ingreso') }}</dt>
                        <dd class="text-sm text-gray-900">{{ $employee->hire_date?->format('d/m/Y') ?? '—' }}</dd>
                    </div>
                </dl>

                <div class="mt-6">
                    <a href="{{ route('employees.index') }}" class="text-emerald-600 hover:underline text-sm">
                        {{ __('← Volver al listado') }}
                    </a>
                </div>
            </x-ui.card>

            <x-ui.card padding="p-6 space-y-4">
                <h3 class="font-semibold text-gray-800">{{ __('Carga de trabajo') }}</h3>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <x-ui.stat-card :label="__('Tareas asignadas')" :value="$metrics['assigned_tasks']" color="emerald" />
                    <x-ui.stat-card :label="__('Activas ahora')" :value="$metrics['active_tasks']" color="blue" />
                    <x-ui.stat-card :label="__('Bloqueadas')" :value="$metrics['blocked_tasks']" color="red" />
                    <x-ui.stat-card :label="__('Atrasadas')" :value="$metrics['overdue_tasks']" color="yellow" />
                </div>

                <dl class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm pt-2">
                    <div>
                        <dt class="text-gray-500">{{ __('Tareas completadas') }}</dt>
                        <dd class="font-medium text-gray-900">{{ $metrics['completed_tasks'] }}</dd>
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
        </div>
    </div>

    @can('update', $employee)
        <x-ui.form-modal name="edit-employee" :title="__('Editar empleado')" :subtitle="$employee->fullName()"
            :action="route('employees.update', $employee)" method="PUT">
            @include('employees.html.partials.form')
        </x-ui.form-modal>

        @if ($errors->any())
            <div x-data x-init="$dispatch('open-modal', 'edit-employee')"></div>
        @endif
    @endcan
</x-app-layout>
