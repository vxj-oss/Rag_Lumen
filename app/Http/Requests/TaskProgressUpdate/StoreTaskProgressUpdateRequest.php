<?php

namespace App\Http\Requests\TaskProgressUpdate;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTaskProgressUpdateRequest extends FormRequest
{

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('task'));
    }

    
    public function rules(): array
    {
        return [
            'new_percentage' => ['required', 'integer', 'between:0,100'],
            'comment' => ['nullable', 'string'],
        ];
    }
}
