<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="$client->nombre" :subtitle="$client->sector ?? __('Cliente')">
            @can('update', $client)
                <x-slot name="actions">
                    <x-ui.secondary-button x-data @click="$dispatch('open-modal', 'edit-client')" class="min-h-[44px]">
                        {{ __('Editar') }}
                    </x-ui.secondary-button>
                </x-slot>
            @endcan
        </x-ui.page-header>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div>
                <a href="{{ route('clients.index') }}" class="inline-flex items-center min-h-[44px] text-sm text-emerald-600 hover:text-emerald-800 font-medium">
                    {{ __('← Volver a clientes') }}
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                <x-ui.card class="lg:col-span-1">
                    <div class="flex gap-2 mb-4">
                        <x-ui.badge :color="$client->estado === 'active' ? 'green' : 'gray'">
                            {{ $client->estado === 'active' ? __('Activo') : __('Inactivo') }}
                        </x-ui.badge>
                        @if ($client->sector)
                            <x-ui.badge color="blue">{{ $client->sector }}</x-ui.badge>
                        @endif
                    </div>

                    <dl class="space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-gray-500">{{ __('RUC / Identificador fiscal') }}</dt>
                            <dd class="text-gray-900 text-right">{{ $client->identificacion_fiscal ?? '—' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-gray-500">{{ __('Contacto principal') }}</dt>
                            <dd class="text-gray-900 text-right">{{ $client->nombre_contacto ?? '—' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-gray-500">{{ __('Email') }}</dt>
                            <dd class="text-gray-900 text-right truncate">{{ $client->correo ?? '—' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-gray-500">{{ __('Teléfono') }}</dt>
                            <dd class="text-gray-900 text-right">{{ $client->telefono ?? '—' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3 pt-3 border-t border-gray-100">
                            <dt class="text-gray-500">{{ __('Proyectos') }}</dt>
                            <dd class="text-gray-900 text-right">{{ $client->projects->count() }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-gray-500">{{ __('Presupuesto acumulado') }}</dt>
                            <dd class="text-gray-900 text-right">{{ number_format($totalBudget, 2) }}</dd>
                        </div>
                    </dl>
                </x-ui.card>

                <x-ui.card padding="p-6 space-y-4" class="lg:col-span-2">
                    <h3 class="font-semibold text-gray-800">{{ __('Historial de proyectos') }}</h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Proyecto') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Estado') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Presupuesto') }}</th>
                                    <th class="px-4 py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($client->projects as $project)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-900">
                                            {{ $project->nombre }}
                                            <span class="text-xs text-gray-400">{{ $project->codigo }}</span>
                                        </td>
                                        <td class="px-4 py-2 text-sm">
                                            <x-ui.badge :color="$project->estado->color()">{{ $project->estado->label() }}</x-ui.badge>
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-500">{{ $project->presupuesto ? number_format((float) $project->presupuesto, 2) : '—' }}</td>
                                        <td class="px-4 py-2 text-right text-sm">
                                            <a href="{{ route('projects.show', $project) }}" class="text-emerald-600 hover:underline">{{ __('Ver') }}</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-6 text-sm text-gray-500 text-center">{{ __('Sin proyectos asociados.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-ui.card>
            </div>
        </div>
    </div>

    @can('update', $client)
        <x-ui.form-modal name="edit-client" :title="__('Editar cliente')" :subtitle="$client->nombre"
            :action="route('clients.update', $client)" method="PUT" max-width="2xl">
            @include('clients.html.partials.form')
        </x-ui.form-modal>

        @if ($errors->any())
            <div x-data x-init="$dispatch('open-modal', 'edit-client')"></div>
        @endif
    @endcan
</x-app-layout>
