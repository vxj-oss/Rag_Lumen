<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="__('Mis actividades')" :subtitle="__('Tu trabajo de hoy, en un vistazo')" :eyebrow="__('Mi día')">
            <x-slot name="actions">
                <a href="{{ route('tasks.index', ['mine' => 1]) }}" class="inline-flex items-center gap-2 px-3 py-2 min-h-[44px] rounded-xl text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-500 hover:shadow-glow hover:-translate-y-0.5 transition">
                    {{ __('Ver en tablero') }}
                </a>
                <a href="{{ route('dashboard.export') }}" class="inline-flex items-center gap-2 px-3 py-2 min-h-[44px] rounded-xl text-sm font-medium text-slate-600 bg-white ring-1 ring-slate-300 hover:bg-slate-50 transition">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2" /></svg>
                    <span class="hidden sm:inline">{{ __('Exportar PDF') }}</span>
                </a>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-ui.day-overview :summary="$summary" />
            <p class="text-sm text-slate-600">{{ __('Horas registradas') }}: <span class="font-semibold tabular-nums">{{ $summary['hours_recorded'] }}</span></p>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <x-ui.stat-card :label="__('Pendientes')" :value="$summary['pending']" color="gray" />
                <x-ui.stat-card :label="__('En progreso')" :value="$summary['in_progress']" color="blue" />
                <x-ui.stat-card :label="__('Por vencer')" :value="$summary['due_soon']" color="yellow" />
                <x-ui.stat-card :label="__('Bloqueadas')" :value="$summary['blocked']" color="red" />
            </div>

            <x-ui.card padding="p-6 space-y-6">
                @forelse ($byState as $stateName => $tasks)
                    <div>
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                            {{ $stateName }} ({{ $tasks->count() }})
                        </h3>
                        <ul class="divide-y divide-slate-100 rounded-xl ring-1 ring-slate-100">
                            @foreach ($tasks as $task)
                                <li class="px-4 py-3 flex items-center gap-3">
                                    <div class="flex-1 min-w-0">
                                        <a href="{{ route('tasks.show', $task) }}" class="text-sm font-medium text-slate-900 hover:text-emerald-700 truncate block">
                                            {{ $task->codigo }} · {{ $task->titulo }}
                                        </a>
                                        <p class="text-xs text-slate-500">
                                            {{ $task->project?->nombre ?? __('Proyecto archivado') }}
                                            @if ($task->fecha_vencimiento)
                                                · {{ __('vence') }} {{ $task->fecha_vencimiento->format('d/m/Y') }}
                                            @endif
                                        </p>
                                    </div>
                                    @if ($task->isOverdue())
                                        <x-ui.badge color="red">{{ __('Atrasada') }}</x-ui.badge>
                                    @endif
                                    @if ($task->isBlockingState())
                                        <x-ui.badge color="red">{{ __('Bloqueada') }}</x-ui.badge>
                                    @endif
                                    <div class="w-24 shrink-0"><x-ui.progress-bar :value="$task->porcentaje_progreso" /></div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @empty
                    <p class="text-sm text-slate-500 text-center py-8">{{ __('No tienes actividades asignadas.') }}</p>
                @endforelse
            </x-ui.card>

            <x-ui.card padding="p-6 space-y-3">
                <h3 class="font-semibold text-slate-800">{{ __('Próximos vencimientos') }}</h3>
                <ul class="divide-y divide-slate-100">
                    @forelse ($upcoming as $task)
                        <li class="py-2 flex items-center justify-between text-sm">
                            <a href="{{ route('tasks.show', $task) }}" class="text-slate-800 hover:text-emerald-700 font-medium truncate">
                                {{ $task->titulo }}
                            </a>
                            <span class="{{ $task->isOverdue() ? 'text-red-600 font-semibold' : 'text-slate-500' }} text-xs whitespace-nowrap ml-3">
                                {{ $task->fecha_vencimiento->format('d/m/Y') }}
                            </span>
                        </li>
                    @empty
                        <li class="py-2 text-sm text-slate-500">{{ __('Nada por vencer.') }}</li>
                    @endforelse
                </ul>
            </x-ui.card>
        </div>
    </div>

    <script>
        (function () {
            let lastSignature = null;

            async function poll() {
                if (document.hidden) return;

                try {
                    const response = await fetch(@js(route('tasks.live-status')), {
                        headers: { 'Accept': 'application/json' },
                    });
                    if (!response.ok) return;

                    const data = await response.json();

                    if (lastSignature === null) {
                        lastSignature = data.signature;
                        return;
                    }

                    if (data.signature !== lastSignature) {
                        window.location.reload();
                    }
                } catch (e) {}
            }

            window.setInterval(poll, 15000);
        })();
    </script>
</x-app-layout>
