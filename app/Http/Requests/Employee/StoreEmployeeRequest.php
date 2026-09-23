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
            'usuario_id' => ['nullable', 'integer', 'exists:users,id', 'unique:empleados,usuario_id'],
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'correo' => array_filter([
                'required', 'string', 'email', 'max:150', 'unique:empleados,correo',
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
                : ['nullable'],
        ];
    }
}
