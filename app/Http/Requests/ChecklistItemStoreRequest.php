<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChecklistItemStoreRequest extends FormRequest
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
            'descripcion' => ['required', 'string'],
            'orden' => ['required', 'integer', 'gt:0'],
        ];
    }
}
