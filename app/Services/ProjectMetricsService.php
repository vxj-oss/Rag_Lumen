<?php

namespace App\Services;

use App\Models\Project;
use App\Models\TaskProgressUpdate;
use Illuminate\Support\Carbon;

class ProjectMetricsService
{
    private const DUE_SOON_DAYS = 3;

    private const TREND_WINDOW_DAYS = 7;

    public function forProject(Project $project): array
    {
        // Solo tareas "hoja" (sin subtareas): si una tarea tiene subtareas, su
        // avance es un cálculo derivado de ellas (ver TaskProgressRollup), así
        // que incluir ambas duplicaría el mismo trabajo en el promedio.
        $tasks = $project->tasks()->whereDoesntHave('subtareas')->get();
        $tasks->loadMissing('state');

        $totalTasks = $tasks->count();
        $completedTasks = $tasks->filter(fn ($task) => $task->isCompletedState());
        $blockedTasks = $tasks->filter(fn ($task) => $task->isBlockingState());
        $overdueTasks = $tasks->filter(fn ($task) => $task->isOverdue());
        $dueSoonTasks = $tasks->filter(function ($task) {
            if ($task->fecha_vencimiento === null || $task->isFinalState()) {
                return false;
            }

            return $task->fecha_vencimiento->between(now()->startOfDay(), now()->addDays(self::DUE_SOON_DAYS)->endOfDay());
        });
        $pendingTasks = $tasks->filter(fn ($task) => $task->isActiveState());

        return [
            'real_progress' => $this->realProgress($tasks),
            'expected_progress' => $this->expectedProgress($project),
            'progress_gap' => round($this->expectedProgress($project) - $this->realProgress($tasks), 1),
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks->count(),
            'pending_tasks' => $pendingTasks->count(),
            'blocked_tasks' => $blockedTasks->count(),
            'overdue_tasks' => $overdueTasks->count(),
            'due_soon_tasks' => $dueSoonTasks->count(),
            'estimated_hours' => round((float) $tasks->sum('horas_estimadas'), 2),
            'actual_hours' => round((float) $tasks->sum('horas_reales'), 2),
            'on_time_completion_rate' => $this->onTimeCompletionRate($completedTasks),
            'trend' => $this->trend($tasks->pluck('id')),
        ];
    }

    private function realProgress($tasks): float
    {
        if ($tasks->isEmpty()) {
            return 0.0;
        }

        $totalWeight = $tasks->sum(fn ($task) => (float) ($task->horas_estimadas ?? 1));

        if ($totalWeight <= 0) {
            return round((float) $tasks->avg('porcentaje_progreso'), 1);
        }

        $weightedSum = $tasks->sum(fn ($task) => $task->porcentaje_progreso * (float) ($task->horas_estimadas ?? 1));

        return round($weightedSum / $totalWeight, 1);
    }

    private function expectedProgress(Project $project): float
    {
        $today = Carbon::now();
        $start = Carbon::instance($project->fecha_inicio);
        $end = Carbon::instance($project->fecha_fin_estimada);

        if ($today->lt($start)) {
            return 0.0;
        }

        if ($today->gte($end)) {
            return 100.0;
        }

        $totalDays = max(1, $start->diffInDays($end));
        $elapsedDays = $start->diffInDays($today);

        return round(min(100, ($elapsedDays / $totalDays) * 100), 1);
    }

    private function onTimeCompletionRate($completedTasks): ?float
    {
        if ($completedTasks->isEmpty()) {
            return null;
        }

        $onTime = $completedTasks->filter(function ($task) {
            if ($task->fecha_vencimiento === null || $task->completado_en === null) {
                return true;
            }

            return $task->completado_en->toDateString() <= $task->fecha_vencimiento->toDateString();
        });

        return round(($onTime->count() / $completedTasks->count()) * 100, 1);
    }

    private function trend($taskIds): string
    {
        if ($taskIds->isEmpty()) {
            return 'no_data';
        }

        $recentDelta = TaskProgressUpdate::whereIn('tarea_id', $taskIds)
            ->where('created_at', '>=', now()->subDays(self::TREND_WINDOW_DAYS))
            ->selectRaw('SUM(CAST(porcentaje_nuevo AS SIGNED) - CAST(porcentaje_anterior AS SIGNED)) as delta')
            ->value('delta');

        $priorDelta = TaskProgressUpdate::whereIn('tarea_id', $taskIds)
            ->whereBetween('created_at', [now()->subDays(self::TREND_WINDOW_DAYS * 2), now()->subDays(self::TREND_WINDOW_DAYS)])
            ->selectRaw('SUM(CAST(porcentaje_nuevo AS SIGNED) - CAST(porcentaje_anterior AS SIGNED)) as delta')
            ->value('delta');

        $recentDelta = (float) ($recentDelta ?? 0);
        $priorDelta = (float) ($priorDelta ?? 0);

        if ($recentDelta === 0.0 && $priorDelta === 0.0) {
            return 'no_data';
        }

        if ($recentDelta > $priorDelta) {
            return 'accelerating';
        }

        if ($recentDelta < $priorDelta) {
            return 'decelerating';
        }

        return 'stable';
    }
}
