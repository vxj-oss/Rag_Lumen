<?php

namespace App\Http\Requests\Task;

use App\Support\Enums\Priority;
use App\Support\Enums\TaskStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('task'));
    }

    
    public function rules(): array
    {
        $rules = [
            'assigned_to' => ['nullable', 'integer', 'exists:employees,id'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', Rule::enum(Priority::class)],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'actual_hours' => ['nullable', 'numeric', 'min:0'],
        ];

        if (! $this->user()->isPlainEmployee()) {
            $rules['status'] = ['required', Rule::enum(TaskStatus::class)];
            $rules['blocked_reason'] = ['nullable', 'string'];
        }

        return $rules;
    }
}
