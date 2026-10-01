<?php

namespace App\Support;

use App\Models\Task;
use App\Models\TaskProgressUpdate;
use App\Models\TaskState;
use App\Models\User;
use App\Support\Enums\TaskStatus;

/**
 * El avance de una actividad se calcula a partir de sus tareas: todas pesan
 * lo mismo (100 / N; con 5 tareas, 20 % cada una). Al llegar a 100 % la
 * actividad pasa a "En revisión"; solo el líder puede aprobarla
 * (ver ReviewTaskEvidenceAction).
 */
class TaskProgressRollup
{
    public static function recalculateAncestors(Task $task, ?User $actor = null): void
    {
        $parent = $task->tareaPadre;

        if ($parent !== null) {
            self::recalculate($parent, $actor);
        }
    }

    public static function recalculate(Task $activity, ?User $actor = null): void
    {
        if ($activity->estado === TaskStatus::Completed) {
            return;
        }

        $children = $activity->subtareas()->get();

        if ($children->isEmpty()) {
            return;
        }

        $completed = $children->filter(fn (Task $t) => $t->estado === TaskStatus::Completed)->count();
        $newPercentage = (int) round($completed / $children->count() * 100);
        $previousPercentage = $activity->porcentaje_progreso;

        $newStatus = match (true) {
            $newPercentage >= 100 => TaskStatus::Review,
            $newPercentage > 0 => TaskStatus::InProgress,
            default => TaskStatus::Pending,
        };

        if ($previousPercentage === $newPercentage && $activity->estado === $newStatus) {
            return;
        }

        if ($previousPercentage !== $newPercentage) {
            TaskProgressUpdate::create([
                'tarea_id' => $activity->id,
                'usuario_id' => $actor?->id,
                'porcentaje_anterior' => $previousPercentage,
                'porcentaje_nuevo' => $newPercentage,
                'comentario' => "Calculado automáticamente: {$completed} de {$children->count()} tareas completadas.",
            ]);
        }

        $activity->porcentaje_progreso = $newPercentage;
        $activity->estado = $newStatus;

        if ($state = TaskState::resolveForStatus($activity->proyecto_id, $newStatus)) {
            $activity->estado_id = $state->id;
        }

        $activity->save();

        ActivityLogger::record($activity, 'progress_recorded', "Avance de la actividad: {$newPercentage}%.");
    }
}
