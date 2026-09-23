<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Support\Enums\RiskLevel;
use App\Support\Enums\TaskStatus;
use Illuminate\Support\Carbon;

class ProjectRiskService
{
    public function __construct(private ProjectMetricsService $metricsService) {}

    public function calculate(Project $project): array
    {
        // Igual que en ProjectMetricsService: solo tareas hoja, para no contar
        // dos veces el mismo trabajo entre una tarea y sus subtareas.
        $tasks = $project->tasks()->whereDoesntHave('subtareas')->get();
        $tasks->loadMissing('state');
        $metrics = $this->metricsService->forProject($project);

        $delayScore = $this->delayScore($tasks);
        $blockedScore = $this->blockedScore($tasks);
        $progressGapScore = $this->progressGapScore($metrics['progress_gap']);
        $deadlinePressureScore = $this->deadlinePressureScore($project, $metrics['real_progress']);
        $workloadScore = $this->workloadScore($project);
        $priorityModifier = config('risk.priority_modifier')[$project->prioridad->value] ?? 0;

        $weights = config('risk.weights');

        $rawScore = ($delayScore * $weights['delay'])
            + ($blockedScore * $weights['blocked'])
            + ($progressGapScore * $weights['progress_gap'])
            + ($deadlinePressureScore * $weights['deadline_pressure'])
            + ($workloadScore * $weights['workload'])
            + $priorityModifier;

        $score = (int) round(max(0, min(100, $rawScore)));

        return [
            'score' => $score,
            'level' => RiskLevel::fromScore($score),
            'components' => [
                'delay_score' => round($delayScore, 1),
                'blocked_score' => round($blockedScore, 1),
                'progress_gap_score' => round($progressGapScore, 1),
                'deadline_pressure_score' => round($deadlinePressureScore, 1),
                'workload_score' => round($workloadScore, 1),
                'priority_modifier' => $priorityModifier,
            ],
        ];
    }

    public function recalculate(Project $project): array
    {
        $result = $this->calculate($project);

        $project->forceFill([
            'puntuacion_riesgo' => $result['score'],
            'nivel_riesgo' => $result['level']->value,
            'riesgo_calculado_en' => now(),
        ])->save();

        return $result;
    }

    private function delayScore($tasks): float
    {
        $activeTasks = $tasks->filter(fn (Task $task) => $task->isActiveState());

        if ($activeTasks->isEmpty()) {
            return 0.0;
        }

        $overdueTasks = $activeTasks->filter(fn (Task $task) => $task->isOverdue());

        if ($overdueTasks->isEmpty()) {
            return 0.0;
        }

        $overdueRatio = $overdueTasks->count() / $activeTasks->count();

        $avgOverdueDays = $overdueTasks->avg(fn (Task $task) => Carbon::instance($task->fecha_vencimiento)->diffInDays(now()));

        $saturation = config('risk.delay_days_saturation');
        $normalizedDays = min($avgOverdueDays / $saturation, 1) * 100;

        return min(100, ($overdueRatio * 70) + ($normalizedDays * 0.30));
    }

    private function blockedScore($tasks): float
    {
        $activeTasks = $tasks->filter(fn (Task $task) => $task->isActiveState());

        if ($activeTasks->isEmpty()) {
            return 0.0;
        }

        $blockedTasks = $activeTasks->filter(fn (Task $task) => $task->isBlockingState());

        $blockedRatio = $blockedTasks->count() / $activeTasks->count();

        return min(100, $blockedRatio * 100);
    }

    private function progressGapScore(float $gap): float
    {
        $multiplier = config('risk.progress_gap_multiplier');

        return min(100, max(0, $gap) * $multiplier);
    }

    private function deadlinePressureScore(Project $project, float $realProgress): float
    {
        $daysRemaining = now()->diffInDays($project->fecha_fin_estimada, false);

        if ($daysRemaining <= 0) {
            return 100.0;
        }

        $remainingWorkPct = 100 - $realProgress;
        $factor = config('risk.deadline_pressure_factor');

        return min(100, max(0, ($remainingWorkPct / max($daysRemaining, 1)) * $factor));
    }

    private function workloadScore(Project $project): float
    {
        $members = $project->relationLoaded('members')
            ? $project->members->filter(fn ($member) => $member->pivot->estado === 'active')
            : $project->members()->wherePivot('estado', 'active')->get();

        if ($members->isEmpty()) {
            return 0.0;
        }

        $maxRecommended = config('risk.employee_max_recommended_tasks');
        $activeStatuses = [TaskStatus::Pending->value, TaskStatus::InProgress->value, TaskStatus::Review->value, TaskStatus::Blocked->value];

        $ratios = $members->map(function ($employee) use ($maxRecommended) {
            $activeTaskCount = Task::where('asignado_a', $employee->id)->whereDoesntHave('subtareas')->with('state')->get()
                ->filter(fn (Task $task) => $task->isActiveState())
                ->count();

            return $activeTaskCount / $maxRecommended;
        });

        $avgRatio = $ratios->avg();

        return min(100, max(0, ($avgRatio - 1) * 100));
    }
}
