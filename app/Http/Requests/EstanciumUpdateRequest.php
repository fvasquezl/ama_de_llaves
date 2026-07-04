<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EstanciumUpdateRequest extends FormRequest
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
            'fecha_egreso' => ['nullable', 'date'],
            'estado' => ['required', 'in:activa,alta,traslado,fallecimiento'],
            'notas_medicas' => ['nullable', 'string'],
        ];
    }
}
