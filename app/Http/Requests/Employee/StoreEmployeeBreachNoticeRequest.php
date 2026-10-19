<?php

namespace App\Http\Requests\Employee;

use App\Support\WorkspaceAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeBreachNoticeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('employee'));
    }

    public function rules(): array
    {
        return [
            'fecha_hechos' => ['required', 'date'],
            'falta_id' => ['nullable', 'integer', Rule::exists('faltas', 'id')->where(fn ($q) => $q->where(fn ($area) => $area->whereNull('area')->orWhereIn('area', array_keys(WorkspaceAccess::areasFor($this->user())))))],
            'hechos' => ['required', 'string', 'max:3000'],
            'clausula_infringida' => ['nullable', 'string', 'max:255'],
            'monto' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
        ];
    }
}
