<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="__('Portafolio bajo mi alcance')" :subtitle="__('Riesgo, presupuesto y rendimiento por área')" :eyebrow="__('Gerencia')">
            <x-slot name="actions">
                <a href="{{ route('dashboard.export') }}" class="inline-flex items-center gap-2 px-3 py-2 min-h-[44px] rounded-lg text-sm font-medium text-gray-600 bg-white ring-1 ring-gray-300 hover:bg-gray-50 transition">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2" /></svg>
                    <span class="hidden sm:inline">{{ __('Exportar PDF') }}</span>
                </a>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                <x-ui.stat-card :label="__('Proyectos')" :value="$summary['scope_projects']" color="emerald" />
                <x-ui.stat-card :label="__('En riesgo')" :value="$summary['at_risk_projects']" color="red" />
                <x-ui.stat-card :label="__('Atrasados')" :value="$summary['delayed_projects']" color="yellow" />
                <x-ui.stat-card :label="__('Requieren atención')" :value="$summary['attention_projects']" color="blue" />
                <x-ui.stat-card :label="__('Tareas atrasadas')" :value="$summary['overdue_tasks']" color="yellow" />
                <x-ui.stat-card :label="__('Presupuesto total')" :value="number_format($summary['total_budget'], 0)" color="emerald" />
            </div>

            <x-ui.card padding="p-0">
                <div class="p-6 pb-0">
                    <h3 class="font-semibold text-gray-800">{{ __('Proyectos en mi alcance') }}</h3>
                    <p class="text-xs text-gray-500 mt-0.5">{{ __('Ordenados por riesgo.') }}</p>
                </div>
                <div class="overflow-x-auto mt-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Proyecto') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Cliente') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Riesgo') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Avance') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Presupuesto') }}</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @forelse ($rows as $row)
                                <tr class="hover:bg-emerald-50/40 transition">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                        {{ $row['project']->name }}
                                        <span class="block text-xs text-gray-400 font-normal">{{ $row['project']->code }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $row['project']->client?->name ?? '—' }}</td>
                                    <td class="px-6 py-4">
                                        <x-ui.badge :color="$row['decision']['risk_level']->color()">
                                            {{ $row['decision']['risk_score'] }}/100
                                        </x-ui.badge>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ $row['metrics']['real_progress'] }}% / {{ $row['metrics']['expected_progress'] }}%
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $row['project']->budget ? number_format((float) $row['project']->budget, 0) : '—' }}</td>
                                    <td class="px-6 py-4 text-right text-sm">
                                        <a href="{{ route('projects.show', $row['project']) }}" class="text-emerald-600 hover:underline">{{ __('Ver') }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">{{ __('Sin proyectos en tu alcance.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <x-ui.card>
                    <h3 class="font-semibold text-gray-800 mb-4">{{ __('Riesgo por proyecto') }}</h3>
                    <div class="h-64"><canvas id="chart-risk-by-project"></canvas></div>
                </x-ui.card>
                <x-ui.card>
                    <h3 class="font-semibold text-gray-800 mb-4">{{ __('Avance por proyecto (%)') }}</h3>
                    <div class="h-64"><canvas id="chart-progress-by-project"></canvas></div>
                </x-ui.card>
            </div>

            <x-ui.card padding="p-0">
                <div class="p-6 pb-0">
                    <h3 class="font-semibold text-gray-800">{{ __('Rendimiento por área') }}</h3>
                    <p class="text-xs text-gray-500 mt-0.5">{{ __('Sobre tareas de proyectos en tu alcance.') }}</p>
                </div>
                <div class="overflow-x-auto mt-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Área') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Tareas') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Completadas') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Activas') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Atrasadas') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Personas') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @forelse ($areaPerformance as $row)
                                <tr class="hover:bg-emerald-50/40 transition">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $row['area']->name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $row['total'] }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $row['completed'] }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $row['active'] }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $row['overdue'] }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $row['employees'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">{{ __('Sin actividad por área.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        </div>
    </div>

    <script id="dashboard-data" type="application/json">
        {!! json_encode([
            'riskByProject' => $riskByProject,
            'progressByProject' => $progressByProject,
        ]) !!}
    </script>
    <script>
        window.dashboardData = JSON.parse(document.getElementById('dashboard-data').textContent);
    </script>
    @vite(['resources/views/dashboard/js/dashboard.js'])
</x-app-layout>
