<?php

namespace App\Actions;

use App\Models\Task;
use App\Models\TaskProgressUpdate;
use App\Models\User;
use App\Services\ProjectRiskService;
use App\Support\ActivityLogger;
use App\Support\Enums\TaskStatus;

class RecordTaskProgressAction
{
    public function __construct(private ProjectRiskService $riskService)
    {
    }

    public function execute(Task $task, int $newPercentage, ?string $comment, User $user): TaskProgressUpdate
    {
        $previousPercentage = $task->progress_percentage;

        $update = TaskProgressUpdate::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'previous_percentage' => $previousPercentage,
            'new_percentage' => $newPercentage,
            'comment' => $comment,
        ]);

        $task->progress_percentage = $newPercentage;

        if ($newPercentage >= 100) {
            
            
            $task->status = TaskStatus::Completed;
        } elseif (! in_array($task->status, [TaskStatus::Review, TaskStatus::Blocked, TaskStatus::Cancelled], true)) {
            
            
            
            $task->status = $newPercentage > 0 ? TaskStatus::InProgress : TaskStatus::Pending;
        }

        if ($newPercentage >= 100) {
            $task->completed_at ??= now();
        } else {
            $task->completed_at = null;
        }

        $task->save();

        $this->riskService->recalculate($task->project);

        $description = "Registró avance de {$previousPercentage}% a {$newPercentage}% en la tarea \"{$task->title}\".";

        if (filled($comment)) {
            $description .= " Comentario: {$comment}";
        }

        ActivityLogger::record($task, 'progress_recorded', $description);

        return $update;
    }
}
