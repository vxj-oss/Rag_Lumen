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
<section class="workspace-hero" aria-label="{{ __('Resumen de mi jornada') }}">
    <div class="flex flex-col justify-between gap-6 lg:flex-row lg:items-center">
        <div class="max-w-2xl"><p class="workspace-eyebrow">{{ __('Mi jornada') }} · {{ now()->format('d/m/Y') }}</p><p class="mb-1 text-sm font-medium text-slate-600">{{ __('Hola, :name', ['name' => Str::before($user->name, ' ')]) }}</p><h2 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">{{ __($focus[0]) }}</h2><p class="mt-2 text-sm leading-relaxed text-slate-600">{{ __($focus[1]) }}</p>
            <div class="mt-5 flex flex-wrap gap-2">
                <a href="{{ route('tasks.index', ['mine' => 1]) }}" class="workspace-primary">{{ __('Ver mis actividades') }} →</a>
                @can('create', \App\Models\Task::class)<a href="{{ route('tasks.index', ['create' => 1]) }}" class="workspace-button">+ {{ __('Nueva actividad') }}</a>@endcan
                <a href="{{ route('rag-query.index') }}" class="workspace-button">{{ __('Consultar al asistente') }}</a>
            </div>
        </div>
        <div class="shrink-0 rounded-2xl border border-emerald-200 bg-white/90 p-5 lg:max-w-[220px]"><p class="text-4xl font-extrabold tabular-nums text-emerald-800">{{ $focus[2] }}</p><p class="mt-1 text-sm font-medium text-slate-600">{{ trans_choice($focus[3], $focus[2]) }}</p><a href="{{ route('alerts.index') }}" class="mt-3 inline-flex min-h-[44px] items-center text-sm font-semibold text-emerald-800">{{ __('Revisar alertas') }} →</a></div>
    </div>
</section>
