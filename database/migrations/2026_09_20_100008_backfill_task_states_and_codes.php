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

            $states = TaskState::where('project_id', $project->id)->get()->keyBy('slug');
            $counter = 0;

            foreach (Task::withTrashed()->where('project_id', $project->id)->orderBy('id')->get() as $task) {
                $counter++;

                $slug = match ($task->getAttributes()['status'] ?? null) {
                    'pending' => 'pendiente',
                    'in_progress' => 'en-progreso',
                    'blocked' => 'bloqueada',
                    'review' => 'en-revision',
                    'completed' => 'completada',
                    'cancelled' => 'cancelada',
                    default => 'pendiente',
                };

                $task->forceFill([
                    'status_id' => $states[$slug]->id,
                    'code' => "{$project->code}-T{$counter}",
                ])->save();
            }

            $project->forceFill(['task_counter' => $counter])->save();
        }
    }

    public function down(): void
    {
        Task::query()->update(['status_id' => null, 'code' => null]);
        Project::query()->update(['task_counter' => 0]);
        TaskState::query()->delete();
    }
};
