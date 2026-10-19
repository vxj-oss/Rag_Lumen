<?php

namespace App\Services\Assistant;

use App\Models\User;
use App\Services\ProjectDecisionService;
use App\Services\ProjectMetricsService;
use App\Support\Enums\DecisionSignal;
use App\Support\WorkspaceScope;

class ProjectPortfolio
{
    public function analyze(User $user): array
    {
        $projects = WorkspaceScope::projects($user)->with(['tasks.state', 'members', 'responsibleEmployee'])->get();
        $rows = $projects->map(fn ($project) => ['project' => $project, 'metrics' => app(ProjectMetricsService::class)->forProject($project), 'decision' => app(ProjectDecisionService::class)->evaluate($project)]);
        $attention = $rows->filter(fn ($r) => ! in_array(DecisionSignal::Healthy, $r['decision']['signals'], true))->count();
        $lines = ['Análisis verificado: '.$rows->count().' '.($rows->count() === 1 ? 'proyecto' : 'proyectos').' dentro de tu acceso.',
            'Requieren atención: '.$attention.'. Avance medio por proyecto: '.round($rows->avg('metrics.real_progress') ?? 0, 1).'%.',
            'Trabajo pendiente: '.$rows->sum('metrics.pending_tasks').'; atrasado: '.$rows->sum('metrics.overdue_tasks').'; bloqueado: '.$rows->sum('metrics.blocked_tasks').'. Se cuentan tareas hoja, sin duplicar actividades y subtareas.'];
        $sources = [];
        foreach ($rows->sortByDesc('decision.risk_score')->take(20) as $row) {
            $p = $row['project'];
            $m = $row['metrics'];
            $d = $row['decision'];
            $lines[] = "\n• {$p->codigo} · {$p->nombre} · ".$p->estado->label().' · Avance: '.$m['real_progress'].'% · Riesgo: '.$d['risk_level']->label().' ('.$d['risk_score'].'/100).';
            $lines[] = 'Responsable: '.($p->responsibleEmployee?->fullName() ?? 'Sin asignar').'. Entrega: '.($p->fecha_fin_estimada?->format('d/m/Y') ?? 'Sin fecha').'.';
            $lines[] = implode(' ', $d['reasons']);
            if ($d['recommended_actions']) {
                $lines[] = 'Recomendación: '.implode(' ', $d['recommended_actions']);
            }
            $sources[] = ['kind' => 'record', 'title' => $p->nombre, 'url' => route('projects.show', $p)];
        }
        if ($rows->count() > 20) {
            $lines[] = 'Los totales analizan todos tus proyectos. Se detallan los 20 de mayor riesgo; consulta un proyecto por nombre para ampliarlo.';
        }
        $lines[] = 'Consultado: '.now()->format('d/m/Y H:i').' ('.config('app.timezone').').';

        return ['answer' => implode("\n", $lines), 'sources' => $sources, 'context' => ['entity' => 'projects', 'metric' => 'detail']];
    }
}
