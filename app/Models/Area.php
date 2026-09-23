<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'nombre', 'descripcion', 'activa',
])]
class Area extends Model
{
    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
        ];
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'areas_proyecto', 'area_id', 'proyecto_id')
            ->withPivot(['id', 'lider_area_id', 'porcentaje_presupuesto'])
            ->withTimestamps();
    }
}
