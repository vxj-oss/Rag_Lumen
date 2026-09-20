<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['task_id', 'from_status_id', 'to_status_id', 'user_id', 'comment'])]
class TaskStatusHistory extends Model
{
    protected $table = 'task_status_history';

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function from(): BelongsTo
    {
        return $this->belongsTo(TaskState::class, 'from_status_id');
    }

    public function to(): BelongsTo
    {
        return $this->belongsTo(TaskState::class, 'to_status_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
