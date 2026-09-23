<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tarea_id', 'depende_de_tarea_id'])]
class TaskDependency extends Model
{
    protected $table = 'dependencias_tarea';

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'tarea_id');
    }

    public function dependsOnTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'depende_de_tarea_id');
    }
}
