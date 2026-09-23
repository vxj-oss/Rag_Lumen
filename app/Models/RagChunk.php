<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['documento_id', 'contenido', 'indice_fragmento', 'metadatos', 'referencia_vector'])]
class RagChunk extends Model
{
    protected $table = 'fragmentos_rag';

    protected function casts(): array
    {
        return [
            'metadatos' => 'array',
            'referencia_vector' => 'array',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(RagDocument::class, 'documento_id');
    }
}
