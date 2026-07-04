<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EstanciumStoreRequest extends FormRequest
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
            'residente_id' => ['required', 'integer', 'exists:Residentes,id'],
            'habitacion_id' => ['required', 'integer', 'exists:,id'],
            'fecha_ingreso' => ['required', 'date'],
            'notas_medicas' => ['nullable', 'string'],
        ];
    }
}
