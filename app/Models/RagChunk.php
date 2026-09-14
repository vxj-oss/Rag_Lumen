<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['document_id', 'content', 'chunk_index', 'metadata', 'vector_reference'])]
class RagChunk extends Model
{
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'vector_reference' => 'array',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(RagDocument::class, 'document_id');
    }
}
