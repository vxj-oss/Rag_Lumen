<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskState;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        TaskState::seedDefaults(null);

        foreach (Project::withTrashed()->get() as $project) {
            TaskState::seedDefaults($project->id);

            $states = TaskState::where('proyecto_id', $project->id)->get()->keyBy('slug');
            $counter = 0;

            foreach (Task::withTrashed()->where('proyecto_id', $project->id)->orderBy('id')->get() as $task) {
                $counter++;

                $slug = match ($task->getAttributes()['estado'] ?? null) {
                    'pending' => 'pendiente',
                    'in_progress' => 'en-progreso',
                    'blocked' => 'bloqueada',
                    'review' => 'en-revision',
                    'completed' => 'completada',
                    'cancelled' => 'cancelada',
                    default => 'pendiente',
                };

                $task->forceFill([
                    'estado_id' => $states[$slug]->id,
                    'codigo' => "{$project->codigo}-T{$counter}",
                ])->save();
            }

            $project->forceFill(['contador_tareas' => $counter])->save();
        }
    }

    public function down(): void
    {
        Task::query()->update(['estado_id' => null, 'codigo' => null]);
        Project::query()->update(['contador_tareas' => 0]);
        TaskState::query()->delete();
    }
};
