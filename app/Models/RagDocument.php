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
    'proyecto_id', 'subido_por', 'titulo', 'nombre_archivo', 'ruta_archivo',
    'tipo_mime', 'tipo_origen', 'estado', 'motivo_fallo', 'metadatos',
])]
class RagDocument extends Model
{
    use SoftDeletes;

    protected $table = 'documentos_rag';

    protected function casts(): array
    {
        return [
            'tipo_origen' => RagSourceType::class,
            'estado' => RagDocumentStatus::class,
            'metadatos' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'proyecto_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subido_por');
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(RagChunk::class, 'documento_id');
    }
}
