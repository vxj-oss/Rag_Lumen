<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('client'));
    }

    public function rules(): array
    {
        $clientId = $this->route('client')->id;

        return [
            'nombre' => ['required', 'string', 'max:150'],
            'identificacion_fiscal' => ['nullable', 'string', 'max:50', Rule::unique('clientes', 'identificacion_fiscal')->ignore($clientId)],
            'nombre_contacto' => ['nullable', 'string', 'max:150'],
            'correo' => ['nullable', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'sector' => ['nullable', 'string', 'max:100'],
            'estado' => ['required', 'in:active,inactive'],
        ];
    }
}
