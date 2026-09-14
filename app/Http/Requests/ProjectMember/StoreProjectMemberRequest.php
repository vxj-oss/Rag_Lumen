<?php

namespace App\Http\Requests\ProjectMember;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProjectMemberRequest extends FormRequest
{

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('project'));
    }

    
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'role_in_project' => ['required', 'string', 'max:50'],
            'assigned_at' => ['required', 'date'],
        ];
    }
}
