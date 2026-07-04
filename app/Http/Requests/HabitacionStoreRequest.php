<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HabitacionStoreRequest extends FormRequest
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
            'sucursal_id' => ['required', 'integer', 'exists:Sucursals,id'],
            'numero' => ['required', 'string'],
            'tipo' => ['required', 'in:individual,doble,suite'],
            'piso' => ['required', 'integer', 'gt:0'],
            'capacidad' => ['required', 'integer', 'gt:0'],
            'nfc_tag_uid' => ['nullable', 'string', 'unique:habitacions,nfc_tag_uid'],
            'notas' => ['nullable', 'string'],
        ];
    }
}
