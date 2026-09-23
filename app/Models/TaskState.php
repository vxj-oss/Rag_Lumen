<?php

namespace App\Models;

use App\Support\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'proyecto_id', 'nombre', 'slug', 'color', 'posicion',
    'es_inicial', 'es_final', 'es_bloqueante', 'activo',
])]
class TaskState extends Model
{
    protected $table = 'estados_tarea';

    protected function casts(): array
    {
        return [
            'es_inicial' => 'boolean',
            'es_final' => 'boolean',
            'es_bloqueante' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'proyecto_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'estado_id');
    }

    /**
     * Estados por defecto al crear un proyecto + plantilla global.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function defaultDefinitions(): array
    {
        return [
            ['nombre' => 'Pendiente', 'slug' => 'pendiente', 'color' => 'gray', 'posicion' => 1, 'es_inicial' => true, 'es_final' => false, 'es_bloqueante' => false],
            ['nombre' => 'En progreso', 'slug' => 'en-progreso', 'color' => 'blue', 'posicion' => 2, 'es_inicial' => false, 'es_final' => false, 'es_bloqueante' => false],
            ['nombre' => 'Bloqueada', 'slug' => 'bloqueada', 'color' => 'red', 'posicion' => 3, 'es_inicial' => false, 'es_final' => false, 'es_bloqueante' => true],
            ['nombre' => 'En revisión', 'slug' => 'en-revision', 'color' => 'indigo', 'posicion' => 4, 'es_inicial' => false, 'es_final' => false, 'es_bloqueante' => false],
            ['nombre' => 'Completada', 'slug' => 'completada', 'color' => 'green', 'posicion' => 5, 'es_inicial' => false, 'es_final' => true, 'es_bloqueante' => false],
            ['nombre' => 'Cancelada', 'slug' => 'cancelada', 'color' => 'gray', 'posicion' => 6, 'es_inicial' => false, 'es_final' => true, 'es_bloqueante' => false],
        ];
    }

    public static function seedDefaults(?int $projectId): void
    {
        foreach (self::defaultDefinitions() as $definition) {
            self::firstOrCreate(
                ['proyecto_id' => $projectId, 'slug' => $definition['slug']],
                $definition + ['proyecto_id' => $projectId, 'activo' => true]
            );
        }
    }

    /**
     * Mapeo slug → enum legacy para la doble escritura.
     */
    public static function enumForSlug(string $slug): ?TaskStatus
    {
        return match ($slug) {
            'pendiente' => TaskStatus::Pending,
            'en-progreso' => TaskStatus::InProgress,
            'bloqueada' => TaskStatus::Blocked,
            'en-revision' => TaskStatus::Review,
            'completada' => TaskStatus::Completed,
            'cancelada' => TaskStatus::Cancelled,
            default => null,
        };
    }

    public static function slugForEnum(TaskStatus $status): string
    {
        return match ($status) {
            TaskStatus::Pending => 'pendiente',
            TaskStatus::InProgress => 'en-progreso',
            TaskStatus::Blocked => 'bloqueada',
            TaskStatus::Review => 'en-revision',
            TaskStatus::Completed => 'completada',
            TaskStatus::Cancelled => 'cancelada',
        };
    }

    /**
     * Busca el estado (propio del proyecto, o el global si no hay uno propio)
     * que corresponde a un valor del enum legado de status.
     */
    public static function resolveForStatus(?int $projectId, TaskStatus $status): ?self
    {
        return self::where('slug', self::slugForEnum($status))
            ->where(function ($query) use ($projectId) {
                $query->where('proyecto_id', $projectId)->orWhereNull('proyecto_id');
            })
            ->where('activo', true)
            ->orderByRaw('proyecto_id IS NULL')
            ->first();
    }

    public static function makeSlug(string $name, ?int $projectId, ?int $ignoreId = null): string
    {
        $base = Str::slug($name, '-');
        $base = $base !== '' ? $base : 'estado';
        $slug = $base;
        $counter = 2;

        while (self::where('proyecto_id', $projectId)->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
