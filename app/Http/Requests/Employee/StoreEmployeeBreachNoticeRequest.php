<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

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
            'hechos' => ['required', 'string', 'max:3000'],
            'clausula_infringida' => ['nullable', 'string', 'max:255'],
            'monto' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
        ];
    }
}
