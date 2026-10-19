<section id="project-panel-tasks" role="tabpanel" aria-labelledby="project-tab-tasks" x-show="tab === 'tasks'" x-cloak class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3"><h3 class="workspace-section-title">{{ __('Actividades del proyecto') }}</h3><a href="{{ route('tasks.index', ['project_id' => $project->id]) }}" class="workspace-primary">{{ __('Abrir tablero') }} →</a></div>
    @forelse ($workspaceTasks as $workspaceTask)
        <a href="{{ route('tasks.show', $workspaceTask) }}" class="block rounded-2xl border border-slate-200 bg-white p-5 transition-colors hover:border-emerald-400">
            <div class="flex flex-wrap items-center justify-between gap-3"><div><p class="text-xs text-slate-500">{{ $workspaceTask->codigo }}</p><h4 class="mt-1 font-semibold text-slate-900">{{ $workspaceTask->titulo }}</h4></div><x-ui.badge :color="$workspaceTask->isOverdue() ? 'red' : 'gray'">{{ $workspaceTask->state?->nombre ?? $workspaceTask->estado->label() }}</x-ui.badge></div>
            <p class="my-3 text-sm text-slate-600">{{ $workspaceTask->assignee?->fullName() ?? __('Sin asignar') }} · {{ $workspaceTask->fecha_vencimiento?->format('d/m/Y') ?? __('Sin fecha') }}</p>
            <x-ui.progress-bar :value="$workspaceTask->porcentaje_progreso" />
        </a>
    @empty
        <div class="workspace-empty"><h4 class="font-semibold text-slate-900">{{ __('No hay actividades visibles en este proyecto') }}</h4><p class="mt-2 text-sm text-slate-600">{{ __('Abre el tablero para consultar o crear actividades según tus permisos.') }}</p></div>
    @endforelse
</section>

<section id="project-panel-documents" role="tabpanel" aria-labelledby="project-tab-documents" x-show="tab === 'documents'" x-cloak class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3"><h3 class="workspace-section-title">{{ __('Documentos del proyecto') }}</h3><a href="{{ route('rag-documents.index') }}" class="workspace-button">{{ __('Gestionar documentos') }}</a></div>
    @forelse ($workspaceDocuments as $workspaceDocument)
        <a href="{{ route('rag-documents.source', $workspaceDocument) }}" class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-5 hover:border-emerald-400"><div><h4 class="font-semibold text-slate-900">{{ $workspaceDocument->titulo }}</h4><p class="mt-1 text-sm text-slate-600">{{ $workspaceDocument->nombre_archivo }}</p></div><span class="text-sm font-semibold text-emerald-800">{{ __('Consultar fuente') }} →</span></a>
    @empty
        <div class="workspace-empty"><h4 class="font-semibold text-slate-900">{{ __('No hay documentos disponibles para tu acceso') }}</h4><p class="mt-2 text-sm text-slate-600">{{ __('Los documentos que se carguen y estén dentro de tu alcance aparecerán aquí.') }}</p></div>
    @endforelse
</section>

<section id="project-panel-history" role="tabpanel" aria-labelledby="project-tab-history" x-show="tab === 'history'" x-cloak>
    <x-ui.card><h3 class="workspace-section-title mb-6">{{ __('Últimos movimientos del proyecto') }}</h3>
        <ol class="workspace-timeline">
            @forelse ($workspaceHistory as $event)
                <li><p class="text-sm font-semibold text-slate-900">{{ $event->descripcion }}</p><p class="mt-1 text-xs text-slate-600">{{ $event->user?->name ?? __('Sistema') }} · {{ $event->created_at->format('d/m/Y H:i') }}</p></li>
            @empty
                <li><p class="text-sm text-slate-600">{{ __('Todavía no hay movimientos registrados sobre este proyecto.') }}</p></li>
            @endforelse
        </ol>
    </x-ui.card>
</section>
