<?php

namespace App\Http\Requests\TaskEvidence;

use Illuminate\Foundation\Http\FormRequest;

class RejectTaskEvidenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');

        if (! $this->user()->can('reviewEvidence', $task)) {
            return false;
        }

        return $task->enRevision();
    }

    public function rules(): array
    {
        return [
            'motivo' => ['required', 'string', 'max:1000'],
        ];
    }
}
