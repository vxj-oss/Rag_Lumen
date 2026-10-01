<?php

namespace App\Actions;

use App\Models\Task;
use App\Models\TaskEvidence;
use App\Models\TaskProgressUpdate;
use App\Models\TaskState;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\Enums\TaskStatus;
use App\Support\TaskProgressRollup;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SubmitTaskEvidenceAction
{
    /**
     * Registra la evidencia y marca la tarea como completada. Sin evidencia
     * la tarea no se completa, sea cual sea la vía de entrada.
     *
     * @param  array<int, \Illuminate\Http\UploadedFile>  $archivos
     * @param  array<int, string>  $enlaces
     */
    public function execute(Task $task, array $archivos, array $enlaces, ?string $comentario, User $user): void
    {
        $archivos = array_filter($archivos);
        $enlaces = array_filter($enlaces);

        if ($archivos === [] && $enlaces === []) {
            throw new InvalidArgumentException('Una tarea no puede completarse sin evidencia.');
        }

        DB::transaction(function () use ($task, $archivos, $enlaces, $comentario, $user) {
            foreach ($archivos as $archivo) {
                TaskEvidence::create([
                    'tarea_id' => $task->id,
                    'usuario_id' => $user->id,
                    'tipo' => 'archivo',
                    'ruta_archivo' => $archivo->store('evidencias/tareas', 'local'),
                    'nombre_archivo' => $archivo->getClientOriginalName(),
                    'comentario' => $comentario,
                ]);
            }

            foreach ($enlaces as $enlace) {
                TaskEvidence::create([
                    'tarea_id' => $task->id,
                    'usuario_id' => $user->id,
                    'tipo' => 'enlace',
                    'url' => $enlace,
                    'comentario' => $comentario,
                ]);
            }

            TaskProgressUpdate::create([
                'tarea_id' => $task->id,
                'usuario_id' => $user->id,
                'porcentaje_anterior' => $task->porcentaje_progreso,
                'porcentaje_nuevo' => 100,
                'comentario' => 'Tarea completada con evidencia.',
            ]);

            $task->estado = TaskStatus::Completed;
            $task->porcentaje_progreso = 100;
            $task->completado_en = now();

            if ($state = TaskState::resolveForStatus($task->proyecto_id, TaskStatus::Completed)) {
                $task->estado_id = $state->id;
            }

            $task->save();

            ActivityLogger::record($task, 'updated', "Completó la tarea \"{$task->titulo}\" con evidencia.");

            TaskProgressRollup::recalculateAncestors($task, $user);
        });
    }
}
