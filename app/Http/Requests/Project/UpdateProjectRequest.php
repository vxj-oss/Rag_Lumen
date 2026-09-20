<?php

namespace App\Http\Requests\Project;

use App\Support\Enums\Priority;
use App\Support\Enums\ProjectStatus;
use App\Support\Enums\ProjectType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('project'));
    }

    
    public function rules(): array
    {
        $projectId = $this->route('project')->id;

        return [
            'code' => ['required', 'string', 'max:30', Rule::unique('projects', 'code')->ignore($projectId)],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::enum(ProjectType::class)],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'manager_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'area_ids' => ['nullable', 'array'],
            'area_ids.*' => ['integer', 'exists:areas,id'],
            'start_date' => ['required', 'date'],
            'estimated_end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'actual_end_date' => ['nullable', 'date'],
            'status' => ['required', Rule::enum(ProjectStatus::class)],
            'priority' => ['required', Rule::enum(Priority::class)],
            'responsible_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'observations' => ['nullable', 'string'],
        ];
    }
}
