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

        $project = Project::find($this->input('project_id'));

        return $project !== null
            && $project->responsible_employee_id !== null
            && $this->user()->employee?->id === $project->responsible_employee_id;
    }

    public function rules(): array
    {
        $projectId = $this->input('project_id');

        return [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'area_id' => [
                'nullable', 'integer',
                Rule::exists('project_areas', 'area_id')->where('project_id', $projectId),
            ],
            'assigned_to' => [
                'nullable', 'integer', 'exists:employees,id',
                function (string $attribute, mixed $value, \Closure $fail) use ($projectId) {
                    if (! $this->belongsToProject((int) $value, $projectId)) {
                        $fail('El responsable debe ser miembro activo del proyecto.');
                    } elseif ($this->filled('area_id') && ! $this->belongsToArea((int) $value, (int) $this->input('area_id'))) {
                        $fail('El responsable debe pertenecer al área seleccionada.');
                    }
                },
            ],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'status_id' => [
                'required', 'integer',
                Rule::exists('task_statuses', 'id')->where(function ($query) use ($projectId) {
                    $query->where('active', true)
                        ->where(function ($query) use ($projectId) {
                            $query->whereNull('project_id')->orWhere('project_id', $projectId);
                        });
                }),
            ],
            'priority' => ['required', Rule::enum(Priority::class)],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'actual_hours' => ['nullable', 'numeric', 'min:0'],
            'dependency_ids' => ['nullable', 'array'],
            'dependency_ids.*' => [
                'integer', 'exists:tasks,id',
                function (string $attribute, mixed $value, \Closure $fail) use ($projectId) {
                    $dependency = Task::find($value);

                    if ($dependency === null || (int) $dependency->project_id !== (int) $projectId) {
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

        return ProjectMember::where('project_id', $projectId)
            ->where('employee_id', $employeeId)
            ->where('status', 'active')
            ->exists();
    }

    private function belongsToArea(int $employeeId, int $areaId): bool
    {
        return Employee::where('id', $employeeId)->where('area_id', $areaId)->exists();
    }
}
