<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['conversation_id', 'role', 'content', 'sources'])]
class RagMessage extends Model
{
    protected function casts(): array
    {
        return [
            'sources' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(RagConversation::class, 'conversation_id');
    }
}
