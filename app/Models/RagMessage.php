<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['conversacion_id', 'rol', 'contenido', 'fuentes'])]
class RagMessage extends Model
{
    protected $table = 'mensajes_rag';

    protected function casts(): array
    {
        return [
            'fuentes' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(RagConversation::class, 'conversacion_id');
    }
}
