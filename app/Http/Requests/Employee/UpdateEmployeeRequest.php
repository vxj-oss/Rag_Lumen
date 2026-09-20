<?php

namespace App\Http\Requests\Employee;

use App\Support\Enums\EmployeeSpecialty;
use App\Support\Enums\EmployeeStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateEmployeeRequest extends FormRequest
{

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('employee'));
    }

    
    public function rules(): array
    {
        $employee = $this->route('employee');
        $employeeId = $employee->id;
        $hasAccess = $employee->user_id !== null;
        $creatingAccess = ! $hasAccess && $this->boolean('create_access');

        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id', Rule::unique('employees', 'user_id')->ignore($employeeId)],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => array_filter([
                'required', 'string', 'email', 'max:150', Rule::unique('employees', 'email')->ignore($employeeId),
                $creatingAccess ? Rule::unique('users', 'email') : null,
            ]),
            'phone' => ['nullable', 'string', 'max:30'],
            'position' => ['nullable', 'string', 'max:100'],
            'specialty' => ['required', Rule::enum(EmployeeSpecialty::class)],
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'status' => ['required', Rule::enum(EmployeeStatus::class)],
            'hire_date' => ['nullable', 'date'],
            'create_access' => ['sometimes', 'boolean'],
            'password' => $creatingAccess
                ? ['required', 'string', Password::defaults(), 'confirmed']
                : ['nullable', 'string', Password::defaults(), 'confirmed'],
        ];
    }
}
