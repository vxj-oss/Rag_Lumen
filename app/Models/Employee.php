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
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'usuario_id', 'nombres', 'apellidos', 'documento_identidad', 'correo', 'telefono',
    'cargo', 'especialidad', 'area_id', 'estado', 'fecha_contratacion', 'firma_path',
    'normativa_version_aceptada', 'normativa_tdr_id', 'normativa_aceptada_en',
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
            'normativa_aceptada_en' => 'datetime',
        ];
    }

    public function tdrAceptado(): BelongsTo
    {
        return $this->belongsTo(TerminosReferencia::class, 'normativa_tdr_id');
    }

    /** TDR que corresponde al perfil del colaborador según su especialidad (null si no hay uno único). */
    public function tdrSugerido(): ?TerminosReferencia
    {
        $area = $this->especialidad?->tdrArea();

        return $area === null ? null : TerminosReferencia::porArea($area);
    }

    /** ¿Aceptó la versión vigente de este TDR? */
    public function aceptoTdr(TerminosReferencia $tdr): bool
    {
        return $this->normativa_tdr_id === $tdr->id && $this->normativa_version_aceptada === $tdr->version;
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

    public function tieneFirma(): bool
    {
        return $this->firma_path !== null;
    }

    public function firmaBase64(): ?string
    {
        if ($this->firma_path === null || ! Storage::disk('local')->exists($this->firma_path)) {
            return null;
        }

        $mime = str_ends_with($this->firma_path, '.png') ? 'image/png' : 'image/jpeg';

        return 'data:'.$mime.';base64,'.base64_encode(Storage::disk('local')->get($this->firma_path));
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
