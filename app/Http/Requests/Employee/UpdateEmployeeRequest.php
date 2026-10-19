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
            'usuario_id' => [Rule::prohibitedIf(! $this->user()->isAdmin()), 'nullable', 'integer', 'exists:users,id', Rule::unique('empleados', 'usuario_id')->ignore($employeeId)],
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'documento_identidad' => ['nullable', 'string', 'max:20', Rule::unique('empleados', 'documento_identidad')->ignore($employeeId)],
            'firma' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
            'correo' => array_filter([
                'required', 'string', 'email', 'max:150', Rule::unique('empleados', 'correo')->ignore($employeeId),
                $creatingAccess ? Rule::unique('users', 'email') : null,
            ]),
            'telefono' => ['nullable', 'string', 'max:30'],
            'cargo' => ['nullable', 'string', 'max:100'],
            'especialidad' => ['required', Rule::enum(EmployeeSpecialty::class), function ($attribute, $value, $fail) { if ($this->user()->isLeader() && !array_key_exists((EmployeeSpecialty::tryFrom($value)?->tdrArea() ?? ''), \App\Support\WorkspaceAccess::areasFor($this->user()))) $fail('Solo puedes gestionar trabajadores de tus áreas.'); }],
            'area_id' => ['nullable', 'integer', 'exists:areas,id', function ($attribute, $value, $fail) { if ($this->user()->isLeader() && !in_array((int)$value, \App\Support\WorkspaceAccess::areaIdsFor($this->user()) ?? [], true)) $fail('No puedes asignar otra área.'); }],
            'estado' => ['required', Rule::enum(EmployeeStatus::class)],
            'fecha_contratacion' => ['nullable', 'date'],
            'create_access' => [Rule::prohibitedIf(! $this->user()->isAdmin()), 'sometimes', 'boolean'],
            'password' => $creatingAccess
                ? ['required', 'string', Password::defaults(), 'confirmed']
                : ['prohibited'],
        ];
    }
}
