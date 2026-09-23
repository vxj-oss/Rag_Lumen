<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="$employee->fullName()" :subtitle="$employee->cargo ?? $employee->especialidad->label()">
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
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div>
                <a href="{{ route('employees.index') }}" class="inline-flex items-center min-h-[44px] text-sm text-emerald-600 hover:text-emerald-800 font-medium">
                    {{ __('← Volver a empleados') }}
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                <x-ui.card class="lg:col-span-1">
                    <h3 class="font-semibold text-gray-800 mb-4">{{ __('Información') }}</h3>
                    <dl class="space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-gray-500">{{ __('Email') }}</dt>
                            <dd class="text-gray-900 text-right truncate">{{ $employee->correo }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-gray-500">{{ __('Teléfono') }}</dt>
                            <dd class="text-gray-900 text-right">{{ $employee->telefono ?? '—' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-gray-500">{{ __('Cargo') }}</dt>
                            <dd class="text-gray-900 text-right">{{ $employee->cargo ?? '—' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-gray-500">{{ __('Especialidad') }}</dt>
                            <dd class="text-gray-900 text-right">{{ $employee->especialidad->label() }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-gray-500">{{ __('Área') }}</dt>
                            <dd class="text-gray-900 text-right">{{ $employee->area?->nombre ?? '—' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 pt-3 border-t border-gray-100">
                            <dt class="text-gray-500">{{ __('Estado') }}</dt>
                            <dd class="text-right">
                                <x-ui.badge :color="$employee->estado->value === 'active' ? 'green' : ($employee->estado->value === 'on_leave' ? 'yellow' : 'gray')">
                                    {{ $employee->estado->label() }}
                                </x-ui.badge>
                            </dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-gray-500">{{ __('Fecha de ingreso') }}</dt>
                            <dd class="text-gray-900 text-right">{{ $employee->fecha_contratacion?->format('d/m/Y') ?? '—' }}</dd>
                        </div>
                    </dl>
                </x-ui.card>

                <x-ui.card padding="p-6 space-y-4" class="lg:col-span-2">
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
