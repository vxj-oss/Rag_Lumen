<?php

namespace App\Models;

use App\Support\Enums\EmployeeSpecialty;
use App\Support\Enums\EmployeeStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'usuario_id', 'nombres', 'apellidos', 'correo', 'telefono',
    'cargo', 'especialidad', 'area_id', 'estado', 'fecha_contratacion',
])]
class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'empleados';

    protected function casts(): array
    {
        return [
            'especialidad' => EmployeeSpecialty::class,
            'estado' => EmployeeStatus::class,
            'fecha_contratacion' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function fullName(): string
    {
        return "{$this->nombres} {$this->apellidos}";
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'miembros_proyecto', 'empleado_id', 'proyecto_id')
            ->using(ProjectMember::class)
            ->withPivot(['id', 'rol_en_proyecto', 'asignado_en', 'retirado_en', 'estado'])
            ->withTimestamps();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'asignado_a');
    }
}
