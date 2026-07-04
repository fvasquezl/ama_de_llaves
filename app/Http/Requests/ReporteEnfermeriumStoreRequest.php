<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReporteEnfermeriumStoreRequest extends FormRequest
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
            'ronda_enfermeria_id' => ['required', 'integer', 'exists:RondaEnfermerias,id'],
            'incidencias' => ['nullable', 'string'],
            'observaciones' => ['nullable', 'string'],
        ];
    }
}
