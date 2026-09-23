<?php

namespace App\Http\Requests\TaskProgressUpdate;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTaskProgressUpdateRequest extends FormRequest
{

    public function authorize(): bool
    {
        if (! $this->user()->can('update', $this->route('task'))) {
            return false;
        }

        return ! $this->route('task')->tieneSubtareas();
    }


    public function rules(): array
    {
        return [
            'new_percentage' => ['required', 'integer', 'between:0,100'],
            'comment' => ['nullable', 'string'],
        ];
    }
}
