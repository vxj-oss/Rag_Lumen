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
                            <dt class="text-gray-500">{{ __('DNI') }}</dt>
                            <dd class="text-gray-900 text-right">{{ $employee->documento_identidad ?? '—' }}</dd>
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

            @can('update', $employee)
                <x-ui.card padding="p-6 space-y-4">
                    <h3 class="font-semibold text-gray-800">{{ __('Documentos') }}</h3>

                    <div class="flex items-center gap-4">
                        @if ($employee->tieneFirma())
                            <img src="{{ route('employees.firma', $employee) }}" alt="{{ __('Firma registrada') }}" class="h-16 border border-gray-200 rounded-lg bg-white p-1">
                            <p class="text-sm text-gray-500">{{ __('Firma registrada. Se usa automáticamente al generar los documentos.') }}</p>
                        @else
                            <p class="text-sm text-gray-500">{{ __('Este colaborador todavía no tiene una firma escaneada. Edítalo para subir una antes de generar el convenio.') }}</p>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center gap-3 pt-2 border-t border-gray-100">
                        <a href="{{ route('employees.contract', $employee) }}"
                           class="inline-flex items-center gap-2 px-3 py-2 min-h-[44px] rounded-lg text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 transition">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            {{ __('Generar convenio de colaboración') }}
                        </a>
                        <button x-data @click="$dispatch('open-modal', 'breach-notice')" type="button"
                                class="inline-flex items-center gap-2 px-3 py-2 min-h-[44px] rounded-lg text-sm font-medium text-red-700 bg-red-50 ring-1 ring-red-200 hover:bg-red-100 transition">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                            {{ __('Generar acta de incumplimiento') }}
                        </button>
                    </div>
                    <p class="text-xs text-gray-400">{{ __('Ambos documentos se generan como PDF con los datos del colaborador y su firma registrada.') }}</p>
                </x-ui.card>
            @endcan
        </div>
    </div>

    @can('update', $employee)
        <x-ui.form-modal name="edit-employee" :title="__('Editar empleado')" :subtitle="$employee->fullName()"
            :action="route('employees.update', $employee)" method="PUT" multipart>
            @include('employees.html.partials.form')
        </x-ui.form-modal>

        <x-ui.form-modal name="breach-notice" :title="__('Generar acta de incumplimiento')" :subtitle="$employee->fullName()"
            :action="route('employees.breach-notice', $employee)" submit-label="{{ __('Generar PDF') }}">
            <p class="text-xs text-gray-500">{{ __('Este documento deja constancia del incumplimiento y, si corresponde, del monto a resarcir. Revísalo con el Gerente General antes de usarlo.') }}</p>
            <div>
                <x-forms.input-label for="fecha_hechos" :value="__('Fecha de los hechos')" />
                <x-forms.text-input id="fecha_hechos" name="fecha_hechos" type="date" class="mt-1 block w-full"
                    :value="old('fecha_hechos', now()->toDateString())" required />
                <x-forms.input-error :messages="$errors->get('fecha_hechos')" class="mt-2" />
            </div>
            <div>
                <x-forms.input-label for="clausula_infringida" :value="__('Cláusula infringida (opcional)')" />
                <x-forms.text-input id="clausula_infringida" name="clausula_infringida" type="text" class="mt-1 block w-full"
                    placeholder="{{ __('Ej: Cuarta - Desvinculación anticipada') }}" :value="old('clausula_infringida')" />
                <x-forms.input-error :messages="$errors->get('clausula_infringida')" class="mt-2" />
            </div>
            <div>
                <x-forms.input-label for="hechos" :value="__('Descripción de los hechos')" />
                <textarea id="hechos" name="hechos" rows="4" required
                    class="mt-1 block w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm text-sm">{{ old('hechos') }}</textarea>
                <x-forms.input-error :messages="$errors->get('hechos')" class="mt-2" />
            </div>
            <div>
                <x-forms.input-label for="monto" :value="__('Monto a resarcir en S/ (opcional)')" />
                <x-forms.text-input id="monto" name="monto" type="number" step="0.01" min="0" class="mt-1 block w-full"
                    :value="old('monto')" />
                <x-forms.input-error :messages="$errors->get('monto')" class="mt-2" />
            </div>
        </x-ui.form-modal>

        @if ($errors->any())
            <div x-data x-init="$dispatch('open-modal', 'edit-employee')"></div>
        @endif
    @endcan
</x-app-layout>
