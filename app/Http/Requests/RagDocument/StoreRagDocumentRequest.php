<?php

namespace App\Http\Requests\RagDocument;

use App\Models\Project;
use App\Models\RagDocument;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRagDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()->can('create', RagDocument::class)) {
            return false;
        }

        if ($this->filled('project_id')) {
            $project = Project::find($this->input('project_id'));

            return $project !== null && $this->user()->can('view', $project);
        }

        return true;
    }

    
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'file' => [
                'required',
                'file',
                'mimes:pdf,docx,txt,md,csv',
                'max:'.config('rag.max_upload_size_kb'),
            ],
        ];
    }
}
