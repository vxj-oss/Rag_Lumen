<?php

namespace App\Http\Requests\Project;

use App\Models\Project;
use App\Support\Enums\Priority;
use App\Support\Enums\ProjectStatus;
use App\Support\Enums\ProjectType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{

    public function authorize(): bool
    {
        return $this->user()->can('create', Project::class);
    }


    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'max:30', 'unique:proyectos,codigo'],
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string'],
            'tipo' => ['required', Rule::enum(ProjectType::class)],
            'cliente_id' => ['nullable', 'integer', 'exists:clientes,id'],
            'empleado_gerente_id' => ['nullable', 'integer', 'exists:empleados,id'],
            'area_ids' => ['nullable', 'array'],
            'area_ids.*' => ['integer', 'exists:areas,id'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin_estimada' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'estado' => ['required', Rule::enum(ProjectStatus::class)],
            'prioridad' => ['required', Rule::enum(Priority::class)],
            'empleado_responsable_id' => ['nullable', 'integer', 'exists:empleados,id'],
            'presupuesto' => ['nullable', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string'],
        ];
    }
}
