<?php

namespace App\Http\Requests\Area;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('area'));
    }

    public function rules(): array
    {
        $areaId = $this->route('area')->id;

        return [
            'nombre' => ['required', 'string', 'max:100', Rule::unique('areas', 'nombre')->ignore($areaId)],
            'descripcion' => ['nullable', 'string'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
