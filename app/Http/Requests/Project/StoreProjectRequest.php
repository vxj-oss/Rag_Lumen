<?php

namespace App\Http\Requests\Project;

use App\Models\Project;
use App\Support\Enums\Priority;
use App\Support\Enums\ProjectStatus;
use App\Support\Enums\ProjectType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{

    public function authorize(): bool
    {
        return $this->user()->can('create', Project::class);
    }

    
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:30', 'unique:projects,code'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::enum(ProjectType::class)],
            'client' => ['nullable', 'string', 'max:150'],
            'start_date' => ['required', 'date'],
            'estimated_end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::enum(ProjectStatus::class)],
            'priority' => ['required', Rule::enum(Priority::class)],
            'responsible_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'observations' => ['nullable', 'string'],
        ];
    }
}
