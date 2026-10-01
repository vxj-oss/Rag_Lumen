<?php

namespace App\Http\Requests\Task;

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
            'asignado_a' => [
                'required', 'integer', 'exists:empleados,id',
                function (string $attribute, mixed $value, \Closure $fail) use ($projectId) {
                    if (! $this->belongsToProject((int) $value, $projectId)) {
                        $fail('El responsable debe ser miembro activo del proyecto.');
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
            'tareas' => ['required', 'array', 'min:1'],
            'tareas.*.titulo' => ['required', 'string', 'max:200'],
            'tareas.*.descripcion' => ['nullable', 'string'],
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
}
