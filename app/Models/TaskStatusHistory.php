<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tarea_id', 'estado_origen_id', 'estado_destino_id', 'usuario_id', 'comentario'])]
class TaskStatusHistory extends Model
{
    protected $table = 'historial_estados_tarea';

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'tarea_id');
    }

    public function from(): BelongsTo
    {
        return $this->belongsTo(TaskState::class, 'estado_origen_id');
    }

    public function to(): BelongsTo
    {
        return $this->belongsTo(TaskState::class, 'estado_destino_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
