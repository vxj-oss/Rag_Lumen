<?php

namespace App\Actions;

use App\Models\Task;
use App\Models\TaskProgressUpdate;
use App\Models\TaskState;
use App\Models\User;
use App\Services\ProjectRiskService;
use App\Support\ActivityLogger;
use App\Support\Enums\TaskStatus;
use Illuminate\Support\Facades\DB;

/**
 * Revisión del líder sobre una actividad completa (todas sus tareas al 100 %).
 */
class ReviewTaskEvidenceAction
{
    public function __construct(private ProjectRiskService $riskService)
    {
    }

    public function approve(Task $activity, User $reviewer): void
    {
        DB::transaction(function () use ($activity, $reviewer) {
            $activity->estado = TaskStatus::Completed;
            $activity->completado_en = now();
            $activity->motivo_rechazo = null;

            if ($state = TaskState::resolveForStatus($activity->proyecto_id, TaskStatus::Completed)) {
                $activity->estado_id = $state->id;
            }

            $activity->save();

            TaskProgressUpdate::create([
                'tarea_id' => $activity->id,
                'usuario_id' => $reviewer->id,
                'porcentaje_anterior' => 100,
                'porcentaje_nuevo' => 100,
                'comentario' => 'Actividad aprobada tras revisar la evidencia.',
            ]);

            ActivityLogger::record($activity, 'progress_recorded', "Aprobó la actividad \"{$activity->titulo}\".");
        });

        $this->riskService->recalculate($activity->project);
    }

    /**
     * Rechaza la actividad: queda "En progreso" con el motivo a la vista y sus
     * tareas se reabren para que el trabajador corrija y vuelva a presentar
     * evidencia (la evidencia anterior se conserva como historial).
     */
    public function reject(Task $activity, User $reviewer, string $motivo): void
    {
        DB::transaction(function () use ($activity, $reviewer, $motivo) {
            $pending = TaskState::resolveForStatus($activity->proyecto_id, TaskStatus::Pending);

            foreach ($activity->subtareas as $child) {
                $child->estado = TaskStatus::Pending;
                $child->estado_id = $pending?->id ?? $child->estado_id;
                $child->porcentaje_progreso = 0;
                $child->completado_en = null;
                $child->save();
            }

            TaskProgressUpdate::create([
                'tarea_id' => $activity->id,
                'usuario_id' => $reviewer->id,
                'porcentaje_anterior' => $activity->porcentaje_progreso,
                'porcentaje_nuevo' => 0,
                'comentario' => "Actividad rechazada: {$motivo}",
            ]);

            $activity->estado = TaskStatus::InProgress;
            $activity->porcentaje_progreso = 0;
            $activity->completado_en = null;
            $activity->motivo_rechazo = $motivo;

            if ($state = TaskState::resolveForStatus($activity->proyecto_id, TaskStatus::InProgress)) {
                $activity->estado_id = $state->id;
            }

            $activity->save();

            ActivityLogger::record($activity, 'progress_recorded', "Rechazó la actividad \"{$activity->titulo}\": {$motivo}");
        });
    }
}
