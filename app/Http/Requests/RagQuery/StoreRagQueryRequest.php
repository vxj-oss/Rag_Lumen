<?php

namespace App\Http\Requests\RagQuery;

use Illuminate\Foundation\Http\FormRequest;

class StoreRagQueryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'max:2000'],
            'project_id' => ['nullable', 'integer', 'exists:proyectos,id'],
            'task_id' => ['nullable', 'integer', 'exists:tareas,id'],
            'conversation_id' => ['nullable', 'integer', 'exists:conversaciones_rag,id'],
        ];
    }
}
