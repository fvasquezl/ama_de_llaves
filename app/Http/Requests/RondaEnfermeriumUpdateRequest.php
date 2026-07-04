<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RondaEnfermeriumUpdateRequest extends FormRequest
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
            'hora_inicio_real' => ['nullable'],
            'hora_fin_real' => ['nullable'],
            'estado' => ['required', 'in:pendiente,en_curso,completada,incompleta'],
            'notas' => ['nullable', 'string'],
        ];
    }
}
