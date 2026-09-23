<?php

namespace App\Http\Requests\Area;

use App\Models\Area;
use Illuminate\Foundation\Http\FormRequest;

class StoreAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Area::class);
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100', 'unique:areas,nombre'],
            'descripcion' => ['nullable', 'string'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
