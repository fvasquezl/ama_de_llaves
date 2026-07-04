<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InspeccionStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'tarea_limpieza_id' => ['required', 'integer', 'exists:TareaLimpiezas,id'],
            'resultado' => ['required', 'in:aprobada,rechazada'],
            'puntaje' => ['nullable', 'integer', 'gt:0'],
            'notas' => ['nullable', 'string'],
        ];
    }
}
