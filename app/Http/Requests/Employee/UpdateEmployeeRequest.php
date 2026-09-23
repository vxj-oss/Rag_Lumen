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
        $hasAccess = $employee->usuario_id !== null;
        $creatingAccess = ! $hasAccess && $this->boolean('create_access');

        return [
            'usuario_id' => ['nullable', 'integer', 'exists:users,id', Rule::unique('empleados', 'usuario_id')->ignore($employeeId)],
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'correo' => array_filter([
                'required', 'string', 'email', 'max:150', Rule::unique('empleados', 'correo')->ignore($employeeId),
                $creatingAccess ? Rule::unique('users', 'email') : null,
            ]),
            'telefono' => ['nullable', 'string', 'max:30'],
            'cargo' => ['nullable', 'string', 'max:100'],
            'especialidad' => ['required', Rule::enum(EmployeeSpecialty::class)],
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'estado' => ['required', Rule::enum(EmployeeStatus::class)],
            'fecha_contratacion' => ['nullable', 'date'],
            'create_access' => ['sometimes', 'boolean'],
            'password' => $creatingAccess
                ? ['required', 'string', Password::defaults(), 'confirmed']
                : ['nullable', 'string', Password::defaults(), 'confirmed'],
        ];
    }
}
