<?php

namespace App\Http\Requests\Task;

use App\Models\Project;
use App\Models\Task;
use App\Support\Enums\Priority;
use App\Support\Enums\RoleName;
use App\Support\Enums\TaskStatus;
use Illuminate\Contracts\Validation\ValidationRule;
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
        return [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'assigned_to' => ['nullable', 'integer', 'exists:employees,id'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::enum(TaskStatus::class)],
            'priority' => ['required', Rule::enum(Priority::class)],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'actual_hours' => ['nullable', 'numeric', 'min:0'],
            'blocked_reason' => ['nullable', 'string'],
        ];
    }
}
