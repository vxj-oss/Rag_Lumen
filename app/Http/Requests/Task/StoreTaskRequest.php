<?php

namespace App\Http\Requests\Task;

use App\Models\Employee;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Support\Enums\Priority;
use App\Support\Enums\RoleName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->can('create', Task::class)) {
            return false;
        }

        if ($this->user()->hasRole(RoleName::Administrator->value)) {
            return true;
        }

        $project = Project::find($this->input('proyecto_id'));

        return $project !== null
            && $project->empleado_responsable_id !== null
            && $this->user()->employee?->id === $project->empleado_responsable_id;
    }

    public function rules(): array
    {
        $projectId = $this->input('proyecto_id');

        return [
            'proyecto_id' => ['required', 'integer', 'exists:proyectos,id'],
            'tarea_padre_id' => [
                'nullable', 'integer', 'exists:tareas,id',
                function (string $attribute, mixed $value, \Closure $fail) use ($projectId) {
                    $parent = Task::find($value);

                    if ($parent === null || (int) $parent->proyecto_id !== (int) $projectId) {
                        $fail('La tarea padre debe pertenecer al mismo proyecto.');
                    }
                },
            ],
            'area_id' => [
                'nullable', 'integer',
                Rule::exists('areas_proyecto', 'area_id')->where('proyecto_id', $projectId),
            ],
            'asignado_a' => [
                'nullable', 'integer', 'exists:empleados,id',
                function (string $attribute, mixed $value, \Closure $fail) use ($projectId) {
                    if (! $this->belongsToProject((int) $value, $projectId)) {
                        $fail('El responsable debe ser miembro activo del proyecto.');
                    } elseif ($this->filled('area_id') && ! $this->belongsToArea((int) $value, (int) $this->input('area_id'))) {
                        $fail('El responsable debe pertenecer al área seleccionada.');
                    }
                },
            ],
            'titulo' => ['required', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string'],
            'estado_id' => [
                'required', 'integer',
                Rule::exists('estados_tarea', 'id')->where(function ($query) use ($projectId) {
                    $query->where('activo', true)
                        ->where(function ($query) use ($projectId) {
                            $query->whereNull('proyecto_id')->orWhere('proyecto_id', $projectId);
                        });
                }),
            ],
            'prioridad' => ['required', Rule::enum(Priority::class)],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_vencimiento' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'horas_estimadas' => ['nullable', 'numeric', 'min:0'],
            'horas_reales' => ['nullable', 'numeric', 'min:0'],
            'dependency_ids' => ['nullable', 'array'],
            'dependency_ids.*' => [
                'integer', 'exists:tareas,id',
                function (string $attribute, mixed $value, \Closure $fail) use ($projectId) {
                    $dependency = Task::find($value);

                    if ($dependency === null || (int) $dependency->proyecto_id !== (int) $projectId) {
                        $fail('Las dependencias deben ser tareas del mismo proyecto.');
                    }
                },
            ],
        ];
    }

    private function belongsToProject(int $employeeId, mixed $projectId): bool
    {
        if ($projectId === null) {
            return false;
        }

        return ProjectMember::where('proyecto_id', $projectId)
            ->where('empleado_id', $employeeId)
            ->where('estado', 'active')
            ->exists();
    }

    private function belongsToArea(int $employeeId, int $areaId): bool
    {
        return Employee::where('id', $employeeId)->where('area_id', $areaId)->exists();
    }
}
