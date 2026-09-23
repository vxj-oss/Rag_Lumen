<?php

namespace App\Http\Requests\Client;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Client::class);
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'identificacion_fiscal' => ['nullable', 'string', 'max:50', 'unique:clientes,identificacion_fiscal'],
            'nombre_contacto' => ['nullable', 'string', 'max:150'],
            'correo' => ['nullable', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'sector' => ['nullable', 'string', 'max:100'],
            'estado' => ['sometimes', 'in:active,inactive'],
        ];
    }
}
