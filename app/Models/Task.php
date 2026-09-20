<?php

namespace App\Models;

use App\Support\Enums\Priority;
use App\Support\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'project_id', 'assigned_to', 'created_by', 'code', 'title', 'description', 'status', 'status_id',
    'priority', 'start_date', 'due_date', 'completed_at', 'progress_percentage', 'estimated_hours',
    'actual_hours', 'blocked_reason',
])]
class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (Task $task) {
            if (filled($task->code) || $task->project_id === null) {
                return;
            }

            $project = $task->project ?? Project::find($task->project_id);

            if ($project === null) {
                return;
            }

            $project->increment('task_counter');

            $task->code = "{$project->code}-T{$project->fresh()->task_counter}";
        });
    }

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => Priority::class,
            'start_date' => 'date',
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'estimated_hours' => 'decimal:2',
            'actual_hours' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOverdue(): bool
    {
        return $this->due_date !== null
            && $this->due_date->isPast()
            && $this->isActiveState();
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(TaskState::class, 'status_id');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(TaskStatusHistory::class)->latest('id');
    }

    public function isCompletedState(): bool
    {
        if ($this->state !== null) {
            return $this->state->is_final && $this->state->slug === 'completada';
        }

        return $this->status === TaskStatus::Completed;
    }

    public function isCancelledState(): bool
    {
        if ($this->state !== null) {
            return $this->state->slug === 'cancelada';
        }

        return $this->status === TaskStatus::Cancelled;
    }

    public function isFinalState(): bool
    {
        if ($this->state !== null) {
            return $this->state->is_final;
        }

        return in_array($this->status, [TaskStatus::Completed, TaskStatus::Cancelled], true);
    }

    public function isBlockingState(): bool
    {
        if ($this->state !== null) {
            return $this->state->is_blocking;
        }

        return $this->status === TaskStatus::Blocked;
    }

    public function isActiveState(): bool
    {
        return ! $this->isFinalState();
    }

    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'task_id', 'depends_on_task_id');
    }

    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'depends_on_task_id', 'task_id');
    }

    public function progressUpdates(): HasMany
    {
        return $this->hasMany(TaskProgressUpdate::class)->latest('created_at');
    }

    public function dependsOnTransitively(Task $other, array $visited = []): bool
    {
        if (in_array($this->id, $visited, true)) {
            return false;
        }

        $visited[] = $this->id;

        foreach ($this->dependencies as $dependency) {
            if ($dependency->id === $other->id) {
                return true;
            }

            if ($dependency->dependsOnTransitively($other, $visited)) {
                return true;
            }
        }

        return false;
    }
}
