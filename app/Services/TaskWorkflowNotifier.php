<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskWorkflowNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TaskWorkflowNotifier
{
    public function schedule(Task $task, string $event): void
    {
        if ($task->tarea_padre_id !== null) {
            return;
        }
        $id = $task->id;
        $key = hash('sha256', $id.'|'.$event.'|'.$task->estado->value.'|'.$task->asignado_a.'|'.$task->updated_at?->format('Y-m-d H:i:s').'|'.$task->motivo_rechazo);
        DB::afterCommit(function () use ($id, $event, $key) {
            try {
                $task = Task::with('assignee.user', 'project.responsibleEmployee.user')->find($id);
                if (! $task) {
                    return;
                }
                $message = match ($event) {
                    'assigned' => 'Te asignaron la actividad «'.$task->titulo.'».', 'review' => 'La actividad «'.$task->titulo.'» tiene evidencias pendientes de revisión.', 'approved' => 'La actividad «'.$task->titulo.'» fue aprobada.', 'returned' => 'La actividad «'.$task->titulo.'» requiere correcciones: '.$task->motivo_rechazo, default => 'Se actualizó la actividad «'.$task->titulo.'».'
                };
                $recipients = $event === 'review'
                    ? User::role('administrator')->get()->push($task->project?->responsibleEmployee?->user)->filter()->unique('id')
                    : collect([$task->assignee?->user])->filter();
                foreach ($recipients as $user) {
                    if (! $user->is_active || ! $user->can('view', $task)) {
                        continue;
                    }
                    $exists = $user->notifications()->where('type', TaskWorkflowNotification::class)->where('data->delivery_key', $key)->exists();
                    if (! $exists) {
                        $user->notify(new TaskWorkflowNotification($task, $event, $message, $key));
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('No se pudo completar una notificación de actividad.', ['task_id' => $id, 'event' => $event]);
            }
        });
    }
}
