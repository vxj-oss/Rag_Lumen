<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Fillable(['proyecto_id', 'empleado_id', 'rol_en_proyecto', 'asignado_en', 'retirado_en', 'estado'])]
class ProjectMember extends Pivot
{
    protected $table = 'miembros_proyecto';

    public $incrementing = true;

    protected function casts(): array
    {
        return [
            'asignado_en' => 'date',
            'retirado_en' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'proyecto_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'empleado_id');
    }
}
