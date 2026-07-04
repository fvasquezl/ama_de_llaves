<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HabitacionUpdateRequest extends FormRequest
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
            'numero' => ['required', 'string'],
            'tipo' => ['required', 'in:individual,doble,suite'],
            'piso' => ['required', 'integer', 'gt:0'],
            'capacidad' => ['required', 'integer', 'gt:0'],
            'estado' => ['required', 'in:disponible,ocupada,sucia,en_limpieza,limpia,inspeccionada,fuera_de_servicio'],
            'nfc_tag_uid' => ['nullable', 'string', 'unique:habitacions,nfc_tag_uid'],
            'notas' => ['nullable', 'string'],
        ];
    }
}
