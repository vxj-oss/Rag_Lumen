<?php

namespace App\Models;

use App\Support\Enums\Priority;
use App\Support\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'proyecto_id', 'tarea_padre_id', 'asignado_a', 'creado_por', 'codigo', 'titulo', 'descripcion', 'estado', 'estado_id',
    'prioridad', 'fecha_inicio', 'fecha_vencimiento', 'completado_en', 'porcentaje_progreso', 'horas_estimadas',
    'horas_reales', 'motivo_bloqueo', 'motivo_rechazo',
])]
class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tareas';

    protected static function booted(): void
    {
        static::creating(function (Task $task) {
            if (filled($task->codigo) || $task->proyecto_id === null) {
                return;
            }

            $project = $task->project ?? Project::find($task->proyecto_id);

            if ($project === null) {
                return;
            }

            $project->increment('contador_tareas');

            $task->codigo = "{$project->codigo}-T{$project->fresh()->contador_tareas}";
        });
    }

    protected function casts(): array
    {
        return [
            'estado' => TaskStatus::class,
            'prioridad' => Priority::class,
            'fecha_inicio' => 'date',
            'fecha_vencimiento' => 'date',
            'completado_en' => 'datetime',
            'horas_estimadas' => 'decimal:2',
            'horas_reales' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'proyecto_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'asignado_a');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function isOverdue(): bool
    {
        return $this->fecha_vencimiento !== null
            && $this->fecha_vencimiento->isPast()
            && $this->isActiveState();
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(TaskState::class, 'estado_id');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(TaskStatusHistory::class, 'tarea_id')->latest('id');
    }

    public function isCompletedState(): bool
    {
        if ($this->state !== null) {
            return $this->state->es_final && $this->state->slug === 'completada';
        }

        return $this->estado === TaskStatus::Completed;
    }

    public function isCancelledState(): bool
    {
        if ($this->state !== null) {
            return $this->state->slug === 'cancelada';
        }

        return $this->estado === TaskStatus::Cancelled;
    }

    public function isFinalState(): bool
    {
        if ($this->state !== null) {
            return $this->state->es_final;
        }

        return in_array($this->estado, [TaskStatus::Completed, TaskStatus::Cancelled], true);
    }

    public function isBlockingState(): bool
    {
        if ($this->state !== null) {
            return $this->state->es_bloqueante;
        }

        return $this->estado === TaskStatus::Blocked;
    }

    public function isActiveState(): bool
    {
        return ! $this->isFinalState();
    }

    public function progressUpdates(): HasMany
    {
        return $this->hasMany(TaskProgressUpdate::class, 'tarea_id')->latest('created_at');
    }

    public function tareaPadre(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'tarea_padre_id');
    }

    public function subtareas(): HasMany
    {
        return $this->hasMany(Task::class, 'tarea_padre_id');
    }

    public function tieneSubtareas(): bool
    {
        return $this->relationLoaded('subtareas')
            ? $this->subtareas->isNotEmpty()
            : $this->subtareas()->exists();
    }

    /**
     * En la terminología visible para el usuario, una tarea sin padre es una
     * "Actividad" y una tarea con padre es una "Tarea" dentro de esa actividad.
     * Esto es solo una distinción de presentación: la tabla y las relaciones
     * siguen llamándose igual.
     */
    public function esActividad(): bool
    {
        return $this->tarea_padre_id === null;
    }

    public function evidencias(): HasMany
    {
        return $this->hasMany(TaskEvidence::class, 'tarea_id')->latest('created_at');
    }

    /**
     * Una tarea (hija de una actividad) se completa subiendo evidencia, siempre
     * que no esté completada y su actividad no esté en revisión o aprobada.
     */
    public function puedeRecibirEvidencia(): bool
    {
        if ($this->tarea_padre_id === null || $this->estado === TaskStatus::Completed) {
            return false;
        }

        $actividad = $this->tareaPadre;

        return $actividad !== null
            && in_array($actividad->estado, [TaskStatus::Pending, TaskStatus::InProgress], true);
    }

    /** Actividad con todas sus tareas completadas, esperando al líder. */
    public function enRevision(): bool
    {
        return $this->tarea_padre_id === null && $this->estado === TaskStatus::Review;
    }

    /** La lista de tareas solo se puede modificar mientras la actividad no está en revisión ni aprobada. */
    public function admiteCambiosDeTareas(): bool
    {
        return $this->tarea_padre_id === null
            && in_array($this->estado, [TaskStatus::Pending, TaskStatus::InProgress, TaskStatus::Blocked], true);
    }
}
