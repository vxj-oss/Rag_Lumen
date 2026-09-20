<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="__('Mis proyectos')" :subtitle="__('Estado de tu equipo y tus proyectos')" :eyebrow="__('Operación')">
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
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <x-ui.stat-card :label="__('Mis proyectos')" :value="$summary['my_projects']" color="emerald" />
                <x-ui.stat-card :label="__('Atrasadas del equipo')" :value="$summary['overdue_tasks']" color="yellow" />
                <x-ui.stat-card :label="__('Bloqueadas')" :value="$summary['blocked_tasks']" color="red" />
                <x-ui.stat-card :label="__('Próximas a vencer')" :value="$summary['due_soon_tasks']" color="blue" />
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('projects.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium bg-white ring-1 ring-gray-300 text-gray-700 hover:bg-gray-50 transition">{{ __('Mis proyectos') }}</a>
                <a href="{{ route('tasks.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium bg-white ring-1 ring-gray-300 text-gray-700 hover:bg-gray-50 transition">{{ __('Tareas') }}</a>
                <a href="{{ route('alerts.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium bg-white ring-1 ring-gray-300 text-gray-700 hover:bg-gray-50 transition">{{ __('Alertas') }}</a>
                <a href="{{ route('rag-query.index') }}" class="px-3 py-2 rounded-lg text-sm font-medium bg-white ring-1 ring-gray-300 text-gray-700 hover:bg-gray-50 transition">{{ __('Asistente IA') }}</a>
            </div>

            <x-ui.card padding="p-0">
                <div class="p-6 pb-0">
                    <h3 class="font-semibold text-gray-800">{{ __('Proyectos que lidero') }}</h3>
                </div>
                <div class="overflow-x-auto mt-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Proyecto') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Riesgo') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Avance') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Atrasadas / Bloqueadas') }}</th>
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
                                    <td class="px-6 py-4">
                                        <x-ui.badge :color="$row['decision']['risk_level']->color()">
                                            {{ $row['decision']['risk_score'] }}/100
                                        </x-ui.badge>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ $row['metrics']['real_progress'] }}% / {{ $row['metrics']['expected_progress'] }}%
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ $row['metrics']['overdue_tasks'] }} / {{ $row['metrics']['blocked_tasks'] }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm">
                                        <a href="{{ route('projects.show', $row['project']) }}" class="text-emerald-600 hover:underline">{{ __('Ver') }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">{{ __('No lideras ningún proyecto.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>

            <x-ui.card padding="p-0">
                <div class="p-6 pb-0">
                    <h3 class="font-semibold text-gray-800">{{ __('Tareas atrasadas del equipo') }}</h3>
                </div>
                <div class="overflow-x-auto mt-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Tarea') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Responsable') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Vencía') }}</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @forelse ($overdueTasks as $task)
                                <tr class="hover:bg-emerald-50/40 transition">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                        {{ $task->title }}
                                        <span class="block text-xs text-gray-400 font-normal">{{ $task->code }} · {{ $task->project?->name }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $task->assignee?->fullName() ?? '—' }}</td>
                                    <td class="px-6 py-4 text-sm text-red-600 font-medium">{{ $task->due_date?->format('d/m/Y') }}</td>
                                    <td class="px-6 py-4 text-right text-sm">
                                        <a href="{{ route('tasks.show', $task) }}" class="text-emerald-600 hover:underline">{{ __('Ver') }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-10 text-center text-sm text-gray-500">{{ __('Sin tareas atrasadas. Buen trabajo.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>

            <x-ui.card padding="p-0">
                <div class="p-6 pb-0">
                    <h3 class="font-semibold text-gray-800">{{ __('Carga por empleado') }}</h3>
                </div>
                <div class="overflow-x-auto mt-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Empleado') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Activas') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Completadas') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Atrasadas') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Horas est./reales') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @forelse ($teamLoad as $row)
                                <tr class="hover:bg-emerald-50/40 transition">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $row['employee']->fullName() }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $row['metrics']['active_tasks'] }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $row['metrics']['completed_tasks'] }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $row['metrics']['overdue_tasks'] }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $row['metrics']['estimated_hours'] }} / {{ $row['metrics']['actual_hours'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">{{ __('Sin equipo asignado.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
