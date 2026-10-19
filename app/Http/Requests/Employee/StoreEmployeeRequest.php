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
            'usuario_id' => [Rule::prohibitedIf(!$this->user()->isAdmin()), 'nullable', 'integer', 'exists:users,id', 'unique:empleados,usuario_id'],
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'documento_identidad' => ['nullable', 'string', 'max:20', 'unique:empleados,documento_identidad'],
            'firma' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
            'correo' => array_filter([
                'required', 'string', 'email', 'max:150', 'unique:empleados,correo',
                $creatingAccess ? Rule::unique('users', 'email') : null,
            ]),
            'telefono' => ['nullable', 'string', 'max:30'],
            'cargo' => ['nullable', 'string', 'max:100'],
            'especialidad' => ['required', Rule::enum(EmployeeSpecialty::class), function ($attribute, $value, $fail) { if ($this->user()->isLeader() && !array_key_exists((EmployeeSpecialty::tryFrom($value)?->tdrArea() ?? ''), \App\Support\WorkspaceAccess::areasFor($this->user()))) $fail('Solo puedes gestionar trabajadores de tus áreas.'); }],
            'area_id' => ['nullable', 'integer', 'exists:areas,id', function ($attribute, $value, $fail) { if ($this->user()->isLeader() && !in_array((int)$value, \App\Support\WorkspaceAccess::areaIdsFor($this->user()) ?? [], true)) $fail('No puedes asignar otra área.'); }],
            'estado' => ['required', Rule::enum(EmployeeStatus::class)],
            'fecha_contratacion' => ['nullable', 'date'],
            'create_access' => ['sometimes', 'boolean'],
            'password' => $creatingAccess
                ? ['required', 'string', Password::defaults(), 'confirmed']
                : ['nullable'],
        ];
    }
}
