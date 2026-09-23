<?php

namespace App\Actions;

use App\Models\Task;
use App\Models\TaskProgressUpdate;
use App\Models\TaskState;
use App\Models\User;
use App\Services\ProjectRiskService;
use App\Support\ActivityLogger;
use App\Support\Enums\TaskStatus;
use App\Support\TaskProgressRollup;

class RecordTaskProgressAction
{
    public function __construct(private ProjectRiskService $riskService)
    {
    }

    public function execute(Task $task, int $newPercentage, ?string $comment, User $user): TaskProgressUpdate
    {
        $previousPercentage = $task->porcentaje_progreso;

        $update = TaskProgressUpdate::create([
            'tarea_id' => $task->id,
            'usuario_id' => $user->id,
            'porcentaje_anterior' => $previousPercentage,
            'porcentaje_nuevo' => $newPercentage,
            'comentario' => $comment,
        ]);

        $task->porcentaje_progreso = $newPercentage;

        $newStatus = null;

        if ($newPercentage >= 100) {
            $newStatus = TaskStatus::Completed;
        } elseif (! in_array($task->estado, [TaskStatus::Review, TaskStatus::Blocked, TaskStatus::Cancelled], true)) {
            $newStatus = $newPercentage > 0 ? TaskStatus::InProgress : TaskStatus::Pending;
        }

        if ($newStatus !== null) {
            $task->estado = $newStatus;

            if ($state = TaskState::resolveForStatus($task->proyecto_id, $newStatus)) {
                $task->estado_id = $state->id;
            }
        }

        if ($newPercentage >= 100) {
            $task->completado_en ??= now();
        } else {
            $task->completado_en = null;
        }

        $task->save();

        $this->riskService->recalculate($task->project);

        $description = "Registró avance de {$previousPercentage}% a {$newPercentage}% en la tarea \"{$task->titulo}\".";

        if (filled($comment)) {
            $description .= " Comentario: {$comment}";
        }

        ActivityLogger::record($task, 'progress_recorded', $description);

        TaskProgressRollup::recalculateAncestors($task, $user);

        return $update;
    }
}
