<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['usuario_id', 'proyecto_id', 'tarea_id', 'titulo'])]
class RagConversation extends Model
{
    protected $table = 'conversaciones_rag';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'proyecto_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'tarea_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(RagMessage::class, 'conversacion_id')->orderBy('created_at');
    }
}
