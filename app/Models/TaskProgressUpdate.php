<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tarea_id', 'usuario_id', 'porcentaje_anterior', 'porcentaje_nuevo', 'comentario'])]
class TaskProgressUpdate extends Model
{
    const UPDATED_AT = null;

    protected $table = 'avances_tarea';

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
