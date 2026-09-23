<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['usuario_id', 'sujeto_tipo', 'sujeto_id', 'accion', 'descripcion'])]
class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'registros_actividad';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'sujeto_tipo', 'sujeto_id');
    }
}
