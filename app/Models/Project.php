<?php

namespace App\Models;

use App\Support\Enums\Priority;
use App\Support\Enums\ProjectStatus;
use App\Support\Enums\ProjectType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'codigo', 'nombre', 'descripcion', 'tipo', 'cliente_id', 'fecha_inicio', 'fecha_fin_estimada',
    'fecha_fin_real', 'estado', 'prioridad', 'empleado_responsable_id', 'empleado_gerente_id', 'presupuesto', 'observaciones',
])]
class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'proyectos';

    protected function casts(): array
    {
        return [
            'tipo' => ProjectType::class,
            'estado' => ProjectStatus::class,
            'prioridad' => Priority::class,
            'fecha_inicio' => 'date',
            'fecha_fin_estimada' => 'date',
            'fecha_fin_real' => 'date',
            'presupuesto' => 'decimal:2',
            'riesgo_calculado_en' => 'datetime',
        ];
    }

    public function responsibleEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'empleado_responsable_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'empleado_gerente_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'cliente_id');
    }

    public function areas(): BelongsToMany
    {
        return $this->belongsToMany(Area::class, 'areas_proyecto', 'proyecto_id', 'area_id')
            ->withPivot(['id', 'lider_area_id', 'porcentaje_presupuesto'])
            ->withTimestamps();
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'miembros_proyecto', 'proyecto_id', 'empleado_id')
            ->using(ProjectMember::class)
            ->withPivot(['id', 'rol_en_proyecto', 'asignado_en', 'retirado_en', 'estado'])
            ->withTimestamps();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'proyecto_id');
    }

    public function ragDocuments(): HasMany
    {
        return $this->hasMany(RagDocument::class, 'proyecto_id');
    }
}
