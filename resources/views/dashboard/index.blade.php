<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="__('Dashboard ejecutivo')" :subtitle="__('Panorama general de proyectos, actividades y equipo')">
            <x-slot name="actions">
                <a href="{{ route('dashboard.export') }}" class="inline-flex items-center gap-2 px-3 py-2 min-h-[44px] rounded-xl text-sm font-medium text-slate-600 bg-white ring-1 ring-slate-300 hover:bg-slate-50 transition">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2" /></svg>
                    <span class="hidden sm:inline">{{ __('Exportar PDF') }}</span>
                </a>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-ui.day-overview :summary="$summary" />

            <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
                <x-ui.stat-card :label="__('Proyectos activos')" :value="$summary['active_projects']" color="emerald" />
                <x-ui.stat-card :label="__('Requieren atención')" :value="$summary['attention_projects']" color="red" />
                <x-ui.stat-card :label="__('Actividades atrasadas')" :value="$summary['overdue_tasks']" color="yellow" />
                <x-ui.stat-card :label="__('Actividades completadas')" :value="$summary['completed_tasks']" color="blue" />
            </div>
            <x-ui.card padding="p-0">
                <div class="p-6 pb-0">
                    <h3 class="font-semibold text-slate-800">{{ __('Proyectos que requieren atención') }}</h3>
                    <p class="text-xs text-slate-500 mt-0.5">{{ __('Ordenados por riesgo, generados por el motor de decisiones.') }}</p>
                </div>
                <div class="overflow-x-auto mt-4">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('Proyecto') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('Riesgo') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ __('Señales') }}</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100">
                            @forelse ($attentionList as $item)
                            <tr class="hover:bg-emerald-50/40 transition">
                                <td class="px-6 py-4 text-sm font-medium text-slate-900">{{ $item['project']->nombre }}</td>
                                <td class="px-6 py-4">
                                    <x-ui.badge :color="$item['decision']['risk_level']->color()">
                                        {{ $item['decision']['risk_score'] }}/100
                                    </x-ui.badge>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($item['decision']['signals'] as $signal)
                                        <x-ui.badge :color="$signal->color()">{{ $signal->label() }}</x-ui.badge>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <a href="{{ route('projects.show', $item['project']) }}" class="text-emerald-600 hover:underline">{{ __('Ver') }}</a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-sm text-slate-500">
                                    {{ __('Ningún proyecto requiere atención en este momento.') }}
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
            <details class="group rounded-2xl border border-slate-200 bg-white p-5" x-data @toggle="if ($el.open) requestAnimationFrame(() => window.dispatchEvent(new Event('resize')))">
                <summary class="cursor-pointer text-base font-bold text-slate-900">{{ __('Explorar indicadores y análisis') }}<span class="ml-2 text-sm font-normal text-slate-500">{{ __('Gráficos, estados y rendimiento del equipo') }}</span></summary>
                <div class="mt-6 space-y-6">
            <div>
                <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">{{ __('Proyectos') }}</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                    <x-ui.stat-card :label="__('Totales')" :value="$summary['total_projects']" color="emerald" />
                    <x-ui.stat-card :label="__('Activos')" :value="$summary['active_projects']" color="blue" />
                    <x-ui.stat-card :label="__('Atrasados')" :value="$summary['delayed_projects']" color="yellow" />
                    <x-ui.stat-card :label="__('Críticos')" :value="$summary['critical_projects']" color="red" />
                    <x-ui.stat-card :label="__('Bloqueados')" :value="$summary['blocked_projects']" color="red" />
                </div>
            </div>

            <div>
                <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">{{ __('Actividades y equipo') }}</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                    <x-ui.stat-card :label="__('Actividades pendientes')" :value="$summary['pending_tasks']" color="gray" />
                    <x-ui.stat-card :label="__('Actividades atrasadas')" :value="$summary['overdue_tasks']" color="yellow" />
                    <x-ui.stat-card :label="__('Actividades completadas')" :value="$summary['completed_tasks']" color="emerald" />
                    <x-ui.stat-card :label="__('Empleados sobrecargados')" :value="$summary['overloaded_employees']" color="red" />
                    <x-ui.stat-card :label="__('Requieren atención')" :value="$summary['attention_projects']" color="blue" />
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <x-ui.card>
                    <h3 class="font-semibold text-slate-800 mb-4">{{ __('Proyectos por estado') }}</h3>
                    <div class="h-64"><canvas id="chart-projects-by-status"></canvas></div>
                </x-ui.card>
                <x-ui.card>
                    <h3 class="font-semibold text-slate-800 mb-4">{{ __('Actividades por estado') }}</h3>
                    <div class="h-64"><canvas id="chart-tasks-by-status"></canvas></div>
                </x-ui.card>
                <x-ui.card>
                    <h3 class="font-semibold text-slate-800 mb-4">{{ __('Riesgo por proyecto') }}</h3>
                    <div class="h-64"><canvas id="chart-risk-by-project"></canvas></div>
                </x-ui.card>
                <x-ui.card>
                    <h3 class="font-semibold text-slate-800 mb-4">{{ __('Avance por proyecto (%)') }}</h3>
                    <div class="h-64"><canvas id="chart-progress-by-project"></canvas></div>
                </x-ui.card>
                <x-ui.card>
                    <h3 class="font-semibold text-slate-800 mb-4">{{ __('Rendimiento de empleados (actividades completadas)') }}</h3>
                    <div class="h-64"><canvas id="chart-employee-performance"></canvas></div>
                </x-ui.card>
                <x-ui.card>
                    <h3 class="font-semibold text-slate-800 mb-4">{{ __('Evolución del avance (puntos por semana)') }}</h3>
                    <div class="h-64"><canvas id="chart-progress-evolution"></canvas></div>
                </x-ui.card>
            </div>

                </div>
            </details>
        </div>
    </div>
    <script id="dashboard-data" type="application/json">
        {!! json_encode([
            'projectsByStatus' => $projectsByStatus,
            'tasksByStatus' => $tasksByStatus,
            'riskByProject' => $riskByProject,
            'progressByProject' => $progressByProject,
            'employeePerformance' => $employeePerformance,
            'progressEvolution' => $progressEvolution,
        ]) !!}
    </script>
    <script>
        window.dashboardData = JSON.parse(document.getElementById('dashboard-data').textContent);
    </script>
    @vite(['resources/js/modules/dashboard.js'])
</x-app-layout>