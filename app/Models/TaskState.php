<?php

namespace App\Models;

use App\Support\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'project_id', 'name', 'slug', 'color', 'position',
    'is_initial', 'is_final', 'is_blocking', 'active',
])]
class TaskState extends Model
{
    protected $table = 'task_statuses';

    protected function casts(): array
    {
        return [
            'is_initial' => 'boolean',
            'is_final' => 'boolean',
            'is_blocking' => 'boolean',
            'active' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'status_id');
    }

    /**
     * Estados por defecto al crear un proyecto + plantilla global.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function defaultDefinitions(): array
    {
        return [
            ['name' => 'Pendiente', 'slug' => 'pendiente', 'color' => 'gray', 'position' => 1, 'is_initial' => true, 'is_final' => false, 'is_blocking' => false],
            ['name' => 'En progreso', 'slug' => 'en-progreso', 'color' => 'blue', 'position' => 2, 'is_initial' => false, 'is_final' => false, 'is_blocking' => false],
            ['name' => 'Bloqueada', 'slug' => 'bloqueada', 'color' => 'red', 'position' => 3, 'is_initial' => false, 'is_final' => false, 'is_blocking' => true],
            ['name' => 'En revisión', 'slug' => 'en-revision', 'color' => 'indigo', 'position' => 4, 'is_initial' => false, 'is_final' => false, 'is_blocking' => false],
            ['name' => 'Completada', 'slug' => 'completada', 'color' => 'green', 'position' => 5, 'is_initial' => false, 'is_final' => true, 'is_blocking' => false],
            ['name' => 'Cancelada', 'slug' => 'cancelada', 'color' => 'gray', 'position' => 6, 'is_initial' => false, 'is_final' => true, 'is_blocking' => false],
        ];
    }

    public static function seedDefaults(?int $projectId): void
    {
        foreach (self::defaultDefinitions() as $definition) {
            self::firstOrCreate(
                ['project_id' => $projectId, 'slug' => $definition['slug']],
                $definition + ['project_id' => $projectId, 'active' => true]
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

    public static function makeSlug(string $name, ?int $projectId, ?int $ignoreId = null): string
    {
        $base = Str::slug($name, '-');
        $base = $base !== '' ? $base : 'estado';
        $slug = $base;
        $counter = 2;

        while (self::where('project_id', $projectId)->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
