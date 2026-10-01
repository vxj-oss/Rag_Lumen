<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tarea_id', 'usuario_id', 'tipo', 'ruta_archivo', 'nombre_archivo', 'url', 'comentario',
])]
class TaskEvidence extends Model
{
    protected $table = 'evidencias_tarea';

    public $timestamps = false;

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

    public function esArchivo(): bool
    {
        return $this->tipo === 'archivo';
    }
}
