<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Empleados') }}</h2>
                <p class="text-sm text-gray-500 mt-0.5">{{ __('Gestiona el equipo de la empresa') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('employees.export') }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-gray-600 bg-white ring-1 ring-gray-300 hover:bg-gray-50 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2" /></svg>
                    {{ __('Exportar CSV') }}
                </a>
                @can('create', \App\Models\Employee::class)
                <x-ui.primary-button x-data @click="$dispatch('open-modal', 'create-employee')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    {{ __('Nuevo empleado') }}
                </x-ui.primary-button>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-ui.auth-session-status :status="session('status')" class="px-1" />

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <x-ui.stat-card :label="__('Total de empleados')" :value="$totalCount" color="emerald"
                    icon='<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />' />
                <x-ui.stat-card :label="__('Activos')" :value="$activeCount" color="emerald"
                    icon='<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />' />
                <x-ui.stat-card :label="__('De licencia')" :value="$onLeaveCount" color="yellow"
                    icon='<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />' />
            </div>

            <x-ui.card padding="p-4">
                <form method="GET" action="{{ route('employees.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Buscar') }}</label>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Nombre o email...') }}"
                               class="w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Especialidad') }}</label>
                        <select name="specialty" class="w-full border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm text-sm">
                            <option value="">{{ __('Todas') }}</option>
                            @foreach ($specialties as $specialty)
                                <option value="{{ $specialty->value }}" {{ ($filters['specialty'] ?? '') === $specialty->value ? 'selected' : '' }}>{{ $specialty->label() }}</option>
                            @endforeach
                        </select>
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
                    <div class="sm:col-span-4 flex items-center gap-3">
                        <button type="submit" class="px-3 py-2 rounded-lg text-sm font-medium bg-emerald-600 text-white hover:bg-emerald-700 transition">{{ __('Filtrar') }}</button>
                        @if (array_filter($filters ?? []))
                            <a href="{{ route('employees.index') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('Limpiar filtros') }}</a>
                        @endif
                    </div>
                </form>
            </x-ui.card>

            <x-ui.card padding="p-0">
                <div class="hidden lg:block overflow-x-auto">
                    <table class="w-full divide-y divide-gray-200 table-fixed">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider border-b-2 border-emerald-500/30 w-[28%]">{{ __('Nombre') }}</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider border-b-2 border-emerald-500/30 w-[28%]">{{ __('Email') }}</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider border-b-2 border-emerald-500/30 w-[18%]">{{ __('Especialidad') }}</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider border-b-2 border-emerald-500/30 w-[14%]">{{ __('Estado') }}</th>
                                <th class="px-3 py-3 border-b-2 border-emerald-500/30 w-[12%]"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @forelse ($employees as $employee)
                            <tr class="hover:bg-emerald-50/40 transition">
                                <td class="px-3 py-3 truncate">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 text-white flex items-center justify-center text-sm font-semibold shrink-0 shadow-sm">
                                            {{ Str::of($employee->first_name)->substr(0, 1) }}{{ Str::of($employee->last_name)->substr(0, 1) }}
                                        </div>
                                        <span class="text-sm font-medium text-gray-900 truncate">{{ $employee->fullName() }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-sm text-gray-500 truncate" title="{{ $employee->email }}">{{ $employee->email }}</td>
                                <td class="px-3 py-3 text-sm text-gray-500 truncate">{{ $employee->specialty->label() }}</td>
                                <td class="px-3 py-3">
                                    <x-ui.badge :color="$employee->status->value === 'active' ? 'green' : ($employee->status->value === 'on_leave' ? 'yellow' : 'gray')">
                                        {{ $employee->status->label() }}
                                    </x-ui.badge>
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap text-right text-sm">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('employees.show', $employee) }}" title="{{ __('Ver') }}" class="p-1.5 rounded-lg text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                        </a>
                                        @can('update', $employee)
                                        <button x-data @click="$dispatch('open-modal', 'edit-employee-{{ $employee->id }}')" title="{{ __('Editar') }}" class="p-1.5 rounded-lg text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" /></svg>
                                        </button>
                                        @endcan
                                        @can('delete', $employee)
                                        <form action="{{ route('employees.destroy', $employee) }}" method="POST" @submit.prevent="if (confirm(@js(__('¿Eliminar este empleado?')))) $el.submit()">
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

                            @can('update', $employee)
                            <x-ui.form-modal :name="'edit-employee-'.$employee->id" :title="__('Editar empleado')"
                                :subtitle="$employee->fullName()" :action="route('employees.update', $employee)" method="PUT">
                                <x-slot name="hidden">
                                    <input type="hidden" name="_edit_id" value="{{ $employee->id }}">
                                </x-slot>
                                @include('employees.html.partials.form')
                            </x-ui.form-modal>
                            @endcan
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center">
                                    <svg class="w-10 h-10 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500">{{ __('Todavía no hay empleados registrados.') }}</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="lg:hidden divide-y divide-gray-100">
                    @forelse ($employees as $employee)
                        <div class="p-4 flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 text-white flex items-center justify-center text-sm font-semibold shrink-0 shadow-sm">
                                    {{ Str::of($employee->first_name)->substr(0, 1) }}{{ Str::of($employee->last_name)->substr(0, 1) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $employee->fullName() }}</p>
                                    <p class="text-xs text-gray-400 truncate">{{ $employee->email }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ $employee->specialty->label() }}</p>
                                    <div class="mt-2 flex items-center gap-3 text-sm">
                                        <a href="{{ route('employees.show', $employee) }}" class="text-gray-500 hover:text-emerald-600 transition">{{ __('Ver') }}</a>
                                        @can('update', $employee)
                                        <button x-data @click="$dispatch('open-modal', 'edit-employee-{{ $employee->id }}')" class="text-emerald-600 hover:text-emerald-800 font-medium transition">{{ __('Editar') }}</button>
                                        @endcan
                                        @can('delete', $employee)
                                        <form action="{{ route('employees.destroy', $employee) }}" method="POST" class="inline" @submit.prevent="if (confirm(@js(__('¿Eliminar este empleado?')))) $el.submit()">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 font-medium transition">{{ __('Eliminar') }}</button>
                                        </form>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                            <x-ui.badge :color="$employee->status->value === 'active' ? 'green' : ($employee->status->value === 'on_leave' ? 'yellow' : 'gray')">
                                {{ $employee->status->label() }}
                            </x-ui.badge>
                        </div>
                    @empty
                        <div class="px-6 py-16 text-center">
                            <svg class="w-10 h-10 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">{{ __('Todavía no hay empleados registrados.') }}</p>
                        </div>
                    @endforelse
                </div>
            </x-ui.card>

            {{ $employees->links() }}
        </div>
    </div>

    @can('create', \App\Models\Employee::class)
    <x-ui.form-modal name="create-employee" :title="__('Nuevo empleado')" :subtitle="__('Agrega un miembro al equipo')"
        :action="route('employees.store')" submit-label="{{ __('Crear empleado') }}">
        <x-slot name="hidden">
            <input type="hidden" name="_form" value="create">
        </x-slot>
        @php
            $employee = null;
        @endphp
        @include('employees.html.partials.form')
    </x-ui.form-modal>
    @endcan

    @if ($errors->any())
    @php
    $modalToOpen = old('_edit_id') ? 'edit-employee-'.old('_edit_id') : (old('_form') === 'create' ? 'create-employee' : null);
    @endphp
    @if ($modalToOpen)
    <div x-data x-init="$dispatch('open-modal', @js($modalToOpen))"></div>
    @endif
    @endif
</x-app-layout>