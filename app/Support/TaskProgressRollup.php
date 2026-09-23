<?php

namespace App\Support;

use App\Models\Task;
use App\Models\TaskProgressUpdate;
use App\Models\TaskState;
use App\Models\User;
use App\Support\Enums\TaskStatus;

/**
 * Cuando una tarea tiene subtareas, su avance deja de registrarse a mano:
 * se calcula como el promedio (ponderado por horas estimadas) del avance
 * de sus subtareas directas, igual que el avance de un proyecto se calcula
 * a partir de sus tareas (ver ProjectMetricsService::realProgress).
 */
class TaskProgressRollup
{
    /**
     * Recalcula la tarea padre de $task (si tiene una) y sigue subiendo
     * por la cadena de ancestros mientras el porcentaje siga cambiando.
     */
    public static function recalculateAncestors(Task $task, ?User $actor = null): void
    {
        $parent = $task->tareaPadre;

        if ($parent !== null) {
            self::recalculate($parent, $actor);
        }
    }

    /**
     * Recalcula $node a partir de sus subtareas directas. Si el porcentaje
     * cambió, sigue subiendo hacia su propio padre.
     */
    public static function recalculate(Task $node, ?User $actor = null): void
    {
        $children = $node->subtareas()->get();

        if ($children->isEmpty()) {
            return;
        }

        $totalWeight = $children->sum(fn (Task $t) => (float) ($t->horas_estimadas ?? 1));

        $newPercentage = $totalWeight > 0
            ? (int) round($children->sum(fn (Task $t) => $t->porcentaje_progreso * (float) ($t->horas_estimadas ?? 1)) / $totalWeight)
            : (int) round((float) $children->avg('porcentaje_progreso'));

        $previousPercentage = $node->porcentaje_progreso;

        if ($previousPercentage === $newPercentage) {
            return;
        }

        TaskProgressUpdate::create([
            'tarea_id' => $node->id,
            'usuario_id' => $actor?->id,
            'porcentaje_anterior' => $previousPercentage,
            'porcentaje_nuevo' => $newPercentage,
            'comentario' => 'Calculado automáticamente a partir del avance de sus subtareas.',
        ]);

        $node->porcentaje_progreso = $newPercentage;

        $newStatus = null;

        if ($newPercentage >= 100) {
            $newStatus = TaskStatus::Completed;
        } elseif (! in_array($node->estado, [TaskStatus::Review, TaskStatus::Blocked, TaskStatus::Cancelled], true)) {
            $newStatus = $newPercentage > 0 ? TaskStatus::InProgress : TaskStatus::Pending;
        }

        if ($newStatus !== null) {
            $node->estado = $newStatus;

            if ($state = TaskState::resolveForStatus($node->proyecto_id, $newStatus)) {
                $node->estado_id = $state->id;
            }
        }

        $node->completado_en = $newPercentage >= 100 ? ($node->completado_en ?? now()) : null;

        $node->save();

        ActivityLogger::record($node, 'progress_recorded', "Avance recalculado automáticamente a {$newPercentage}% a partir de sus subtareas.");

        self::recalculateAncestors($node, $actor);
    }
}
