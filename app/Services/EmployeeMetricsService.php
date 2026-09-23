<?php

namespace App\Services;

use App\Models\Employee;

class EmployeeMetricsService
{
    public function forEmployee(Employee $employee): array
    {
        // Solo tareas hoja: una tarea con subtareas no es "trabajo propio" del
        // empleado, su avance es derivado (ver TaskProgressRollup).
        $tasks = $employee->tasks()->whereDoesntHave('subtareas')->get();
        $tasks->loadMissing('state');

        $completedTasks = $tasks->filter(fn ($task) => $task->isCompletedState());
        $activeTasks = $tasks->filter(fn ($task) => $task->isActiveState());

        return [
            'assigned_tasks' => $tasks->count(),
            'active_tasks' => $activeTasks->count(),
            'completed_tasks' => $completedTasks->count(),
            'blocked_tasks' => $tasks->filter(fn ($task) => $task->isBlockingState())->count(),
            'overdue_tasks' => $tasks->filter(fn ($task) => $task->isOverdue())->count(),
            'estimated_hours' => round((float) $tasks->sum('horas_estimadas'), 2),
            'actual_hours' => round((float) $tasks->sum('horas_reales'), 2),
            'on_time_completion_rate' => $this->onTimeCompletionRate($completedTasks),
        ];
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
}
