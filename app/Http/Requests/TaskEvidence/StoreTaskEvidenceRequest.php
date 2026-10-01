<?php

namespace App\Http\Requests\TaskEvidence;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreTaskEvidenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');

        if (! $this->user()->can('submitEvidence', $task)) {
            return false;
        }

        return $task->puedeRecibirEvidencia();
    }

    public function rules(): array
    {
        return [
            'comentario' => ['nullable', 'string', 'max:2000'],
            'enlaces' => ['nullable', 'array'],
            'enlaces.*' => ['nullable', 'url', 'max:500'],
            'archivos' => ['nullable', 'array'],
            'archivos.*' => ['file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $tieneArchivos = count(array_filter($this->file('archivos', []))) > 0;
            $tieneEnlaces = count(array_filter($this->input('enlaces', []))) > 0;

            if (! $tieneArchivos && ! $tieneEnlaces) {
                $validator->errors()->add('archivos', 'Sube al menos un archivo o un enlace como evidencia.');
            }
        });
    }
}
