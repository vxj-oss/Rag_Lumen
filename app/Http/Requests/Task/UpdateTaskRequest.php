<?php

namespace App\Http\Requests\Task;

use App\Models\ProjectMember;
use App\Models\TaskState;
use App\Support\Enums\Priority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('task'));
    }

    public function rules(): array
    {
        $task = $this->route('task');

        $rules = [
            'assigned_to' => [
                'nullable', 'integer', 'exists:employees,id',
                function (string $attribute, mixed $value, \Closure $fail) use ($task) {
                    if (! ProjectMember::where('project_id', $task->project_id)
                        ->where('employee_id', (int) $value)
                        ->where('status', 'active')
                        ->exists()) {
                        $fail('El responsable debe ser miembro activo del proyecto.');
                    }
                },
            ],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', Rule::enum(Priority::class)],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'actual_hours' => ['nullable', 'numeric', 'min:0'],
        ];

        if (! $this->user()->isPlainEmployee()) {
            $rules['status_id'] = [
                'sometimes', 'integer',
                Rule::exists('task_statuses', 'id')->where(function ($query) use ($task) {
                    $query->where('active', true)
                        ->where(function ($query) use ($task) {
                            $query->whereNull('project_id')->orWhere('project_id', $task->project_id);
                        });
                }),
            ];
            $rules['blocked_reason'] = ['nullable', 'string'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled('status_id')) {
                return;
            }

            $state = TaskState::find($this->input('status_id'));

            if ($state !== null && ! $this->user()->can('transitionTo', [$this->route('task'), $state])) {
                $validator->errors()->add('status_id', 'No tienes permiso para mover la tarea a ese estado.');
            }
        });
    }
}
