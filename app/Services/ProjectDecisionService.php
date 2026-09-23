<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Support\Enums\DecisionSignal;
use App\Support\Enums\RiskLevel;

class ProjectDecisionService
{
    public function __construct(
        private ProjectMetricsService $metricsService,
        private ProjectRiskService $riskService,
    ) {}

    public function evaluate(Project $project): array
    {
        $metrics = $this->metricsService->forProject($project);
        $risk = $this->riskService->calculate($project);
        $overloadedEmployees = $this->overloadedEmployees($project);

        $signals = [];
        $reasons = [];
        $actions = [];

        if (in_array($risk['level'], [RiskLevel::High, RiskLevel::Critical], true)) {
            $signals[] = DecisionSignal::HighRisk;
            $reasons[] = "El puntaje de riesgo es {$risk['score']}/100 ({$risk['level']->label()}).";
            $actions[] = 'Priorizar este proyecto en la próxima revisión de gerencia.';
        }

        if ($metrics['overdue_tasks'] > 0 || $metrics['progress_gap'] > config('decisions.delayed_gap_threshold')) {
            $signals[] = DecisionSignal::Delayed;

            if ($metrics['overdue_tasks'] > 0) {
                $reasons[] = "{$metrics['overdue_tasks']} tarea(s) atrasada(s).";
            }

            if ($metrics['progress_gap'] > config('decisions.delayed_gap_threshold')) {
                $reasons[] = "El avance real está {$metrics['progress_gap']} puntos por debajo de lo esperado.";
            }

            $actions[] = 'Replanificar las tareas atrasadas y confirmar la nueva fecha de entrega.';
        }

        if ($metrics['blocked_tasks'] > 0) {
            $signals[] = DecisionSignal::Blocked;
            $reasons[] = "{$metrics['blocked_tasks']} tarea(s) bloqueada(s).";
            $actions[] = 'Revisar las tareas bloqueadas y resolver los impedimentos.';
        }

        $daysRemaining = now()->diffInDays($project->fecha_fin_estimada, false);

        if ($daysRemaining <= config('decisions.deadline_risk_days') && $metrics['real_progress'] < 100) {
            $signals[] = DecisionSignal::DeadlineRisk;
            $reasons[] = $daysRemaining > 0
                ? 'Quedan '.floor($daysRemaining).' día(s) para la fecha límite y el proyecto no está completo.'
                : 'La fecha límite ya pasó y el proyecto no está completo.';
            $actions[] = 'Revisar la fecha de entrega con el cliente o priorizar las tareas restantes.';
        }

        if ($overloadedEmployees->isNotEmpty()) {
            $signals[] = DecisionSignal::ResourceOverload;
            $reasons[] = "{$overloadedEmployees->pluck('name')->join(', ')} tiene(n) sobrecarga de tareas activas.";
            $actions[] = 'Evaluar la reasignación temporal de recursos.';
        }

        if (empty($signals) && $risk['level'] === RiskLevel::Medium) {
            $signals[] = DecisionSignal::AttentionRequired;
            $reasons[] = 'El riesgo es moderado; conviene revisar el proyecto antes de que escale.';
        }

        if (empty($signals)) {
            $signals[] = DecisionSignal::Healthy;
            $reasons[] = 'No se detectan atrasos, bloqueos ni sobrecarga; el proyecto avanza dentro de lo esperado.';
        }

        return [
            'project_id' => $project->id,
            'risk_score' => $risk['score'],
            'risk_level' => $risk['level'],
            'signals' => array_values(array_unique($signals, SORT_REGULAR)),
            'reasons' => $reasons,
            'recommended_actions' => array_values(array_unique($actions)),
        ];
    }

    private function overloadedEmployees(Project $project)
    {
        $members = $project->relationLoaded('members')
            ? $project->members->filter(fn ($member) => $member->pivot->estado === 'active')
            : $project->members()->wherePivot('estado', 'active')->get();

        $maxRecommended = config('risk.employee_max_recommended_tasks');
        $overloadRatio = config('decisions.employee_overload_ratio');

        return $members->map(function ($employee) use ($maxRecommended) {
            $activeTaskCount = Task::where('asignado_a', $employee->id)->with('state')->get()
                ->filter(fn (Task $task) => $task->isActiveState())
                ->count();

            return [
                'name' => $employee->fullName(),
                'ratio' => $activeTaskCount / $maxRecommended,
            ];
        })->filter(fn ($entry) => $entry['ratio'] > $overloadRatio)
            ->map(fn ($entry) => (object) $entry)
            ->values();
    }
}
