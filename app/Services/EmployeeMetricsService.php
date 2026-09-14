<?php

namespace App\Services;

use App\Models\Employee;
use App\Support\Enums\TaskStatus;

class EmployeeMetricsService
{
    private const ACTIVE_STATUSES = [
        TaskStatus::Pending,
        TaskStatus::InProgress,
        TaskStatus::Review,
        TaskStatus::Blocked,
    ];

    public function forEmployee(Employee $employee): array
    {
        $tasks = $employee->relationLoaded('tasks') ? $employee->tasks : $employee->tasks()->get();

        $completedTasks = $tasks->filter(fn ($task) => $task->status === TaskStatus::Completed);
        $activeTasks = $tasks->filter(fn ($task) => in_array($task->status, self::ACTIVE_STATUSES, true));

        return [
            'assigned_tasks' => $tasks->count(),
            'active_tasks' => $activeTasks->count(),
            'completed_tasks' => $completedTasks->count(),
            'blocked_tasks' => $tasks->filter(fn ($task) => $task->status === TaskStatus::Blocked)->count(),
            'overdue_tasks' => $tasks->filter(fn ($task) => $task->isOverdue())->count(),
            'estimated_hours' => round((float) $tasks->sum('estimated_hours'), 2),
            'actual_hours' => round((float) $tasks->sum('actual_hours'), 2),
            'on_time_completion_rate' => $this->onTimeCompletionRate($completedTasks),
        ];
    }

    private function onTimeCompletionRate($completedTasks): ?float
    {
        if ($completedTasks->isEmpty()) {
            return null;
        }

        $onTime = $completedTasks->filter(function ($task) {
            if ($task->due_date === null || $task->completed_at === null) {
                return true;
            }

            return $task->completed_at->toDateString() <= $task->due_date->toDateString();
        });

        return round(($onTime->count() / $completedTasks->count()) * 100, 1);
    }
}
