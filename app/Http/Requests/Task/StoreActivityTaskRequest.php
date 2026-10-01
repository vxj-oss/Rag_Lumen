<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class StoreActivityTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageActivityTasks', $this->route('task'));
    }

    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string'],
        ];
    }
}
