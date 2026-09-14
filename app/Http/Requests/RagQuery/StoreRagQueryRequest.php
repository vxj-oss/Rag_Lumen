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
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'task_id' => ['nullable', 'integer', 'exists:tasks,id'],
            'conversation_id' => ['nullable', 'integer', 'exists:rag_conversations,id'],
        ];
    }
}
