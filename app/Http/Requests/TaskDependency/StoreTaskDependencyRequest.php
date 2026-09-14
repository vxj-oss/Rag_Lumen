<?php

namespace App\Http\Requests\TaskDependency;

use App\Models\Task;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreTaskDependencyRequest extends FormRequest
{

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('task'));
    }

    
    public function rules(): array
    {
        return [
            'depends_on_task_id' => ['required', 'integer', 'exists:tasks,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            
            $task = $this->route('task');
            $dependsOnTaskId = (int) $this->input('depends_on_task_id');

            if ($dependsOnTaskId === $task->id) {
                $validator->errors()->add('depends_on_task_id', 'Una tarea no puede depender de sí misma.');

                return;
            }

            if ($task->dependencies()->where('depends_on_task_id', $dependsOnTaskId)->exists()) {
                $validator->errors()->add('depends_on_task_id', 'Esa dependencia ya existe.');

                return;
            }

            $dependsOnTask = Task::find($dependsOnTaskId);

            if ($dependsOnTask !== null && $dependsOnTask->dependsOnTransitively($task)) {
                $validator->errors()->add('depends_on_task_id', 'Esto crearía una dependencia circular entre tareas.');
            }
        });
    }
}
