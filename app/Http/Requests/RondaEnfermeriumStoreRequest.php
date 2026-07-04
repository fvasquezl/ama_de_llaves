<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RondaEnfermeriumStoreRequest extends FormRequest
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
            'enfermera_id' => ['required', 'integer', 'exists:users,id'],
            'turno' => ['required', 'in:matutino,vespertino,nocturno'],
            'fecha' => ['required', 'date'],
            'hora_inicio_programada' => ['required'],
            'hora_fin_programada' => ['required'],
        ];
    }
}
