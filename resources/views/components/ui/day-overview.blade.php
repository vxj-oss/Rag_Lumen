@props(['summary' => []])
@php
    $user = auth()->user();
    $focus = match (true) {
        $user->isAdmin() => ['Decisiones claras, equipo enfocado', 'Revisa los proyectos que necesitan atención y ayuda a tu equipo a avanzar.', $summary['attention_projects'] ?? 0, 'proyecto requiere atención|proyectos requieren atención'],
        $user->isManager() => ['Tu portafolio, bajo control', 'Prioriza riesgos, consulta el avance y coordina las próximas entregas.', $summary['at_risk_projects'] ?? 0, 'proyecto en riesgo|proyectos en riesgo'],
        $user->isLeader() => ['Un equipo que avanza contigo', 'Resuelve bloqueos y acompaña las actividades que están próximas a vencer.', $summary['blocked_tasks'] ?? 0, 'actividad bloqueada|actividades bloqueadas'],
        default => ['Un paso más cerca de tus objetivos', 'Empieza por tus próximas entregas y mantén al equipo al tanto de tu progreso.', $summary['due_soon'] ?? 0, 'actividad próxima a vencer|actividades próximas a vencer'],
    };
    $focus[2] = count(app(\App\Services\WorkspaceAttentionService::class)->forUser($user));
@endphp
<section class="workspace-hero workspace-overview-compact" aria-label="{{ __('Resumen de mi jornada') }}">
    <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
        <div class="min-w-0">
            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                <p class="text-base font-medium text-slate-900">{{ __('Hola, :name', ['name' => Str::before($user->name, ' ')]) }}</p>
                <p class="text-xs text-slate-500">{{ now()->format('d/m/Y') }}</p>
            </div>
            <div class="mt-3 flex flex-wrap gap-2">
                <a href="{{ route('tasks.index', ['mine' => 1]) }}" class="workspace-primary">{{ __('Mis actividades') }} →</a>
                @can('create', \App\Models\Task::class)<a href="{{ route('tasks.index', ['create' => 1]) }}" class="workspace-button">+ {{ __('Nueva actividad') }}</a>@endcan
                <a href="{{ route('rag-query.index') }}" class="workspace-button">{{ __('Asistente') }}</a>
            </div>
        </div>
        <a href="{{ route('alerts.index') }}" class="workspace-attention workspace-attention-inline">
            <span class="text-2xl font-medium tabular-nums">{{ $focus[2] }}</span>
            <span class="text-sm">{{ trans_choice($focus[3], $focus[2]) }}</span>
            <span aria-hidden="true">→</span>
        </a>
    </div>
</section>
