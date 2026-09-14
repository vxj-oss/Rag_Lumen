<?php

namespace App\Models;

use App\Support\Enums\RagDocumentStatus;
use App\Support\Enums\RagSourceType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'project_id', 'uploaded_by', 'title', 'file_name', 'file_path',
    'mime_type', 'source_type', 'status', 'failure_reason', 'metadata',
])]
class RagDocument extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'source_type' => RagSourceType::class,
            'status' => RagDocumentStatus::class,
            'metadata' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(RagChunk::class, 'document_id');
    }
}
