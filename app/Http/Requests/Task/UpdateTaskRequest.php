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
            'asignado_a' => [
                'nullable', 'integer', 'exists:empleados,id',
                function (string $attribute, mixed $value, \Closure $fail) use ($task) {
                    if (! ProjectMember::where('proyecto_id', $task->proyecto_id)
                        ->where('empleado_id', (int) $value)
                        ->where('estado', 'active')
                        ->exists()) {
                        $fail('El responsable debe ser miembro activo del proyecto.');
                    }
                },
            ],
            'titulo' => ['required', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string'],
            'prioridad' => ['required', Rule::enum(Priority::class)],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_vencimiento' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
        ];

        if (! $this->user()->isPlainEmployee()) {
            $rules['estado_id'] = [
                'sometimes', 'integer',
                Rule::exists('estados_tarea', 'id')->where(function ($query) use ($task) {
                    $query->where('activo', true)
                        ->where(function ($query) use ($task) {
                            $query->whereNull('proyecto_id')->orWhere('proyecto_id', $task->proyecto_id);
                        });
                }),
            ];
            $rules['motivo_bloqueo'] = ['nullable', 'string'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled('estado_id')) {
                return;
            }

            $state = TaskState::find($this->input('estado_id'));

            if ($state !== null && ! $this->user()->can('transitionTo', [$this->route('task'), $state])) {
                $validator->errors()->add('estado_id', 'No tienes permiso para mover la tarea a ese estado.');
            }
        });
    }
}
