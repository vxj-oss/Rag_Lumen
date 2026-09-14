<?php

namespace App\Http\Requests\Employee;

use App\Models\Employee;
use App\Support\Enums\EmployeeSpecialty;
use App\Support\Enums\EmployeeStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreEmployeeRequest extends FormRequest
{

    public function authorize(): bool
    {
        return $this->user()->can('create', Employee::class);
    }

    
    public function rules(): array
    {
        $creatingAccess = $this->boolean('create_access');

        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id', 'unique:employees,user_id'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => array_filter([
                'required', 'string', 'email', 'max:150', 'unique:employees,email',
                $creatingAccess ? Rule::unique('users', 'email') : null,
            ]),
            'phone' => ['nullable', 'string', 'max:30'],
            'position' => ['nullable', 'string', 'max:100'],
            'specialty' => ['required', Rule::enum(EmployeeSpecialty::class)],
            'status' => ['required', Rule::enum(EmployeeStatus::class)],
            'hire_date' => ['nullable', 'date'],
            'create_access' => ['sometimes', 'boolean'],
            'password' => $creatingAccess
                ? ['required', 'string', Password::defaults(), 'confirmed']
                : ['nullable'],
        ];
    }
}
