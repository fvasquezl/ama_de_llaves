<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResidenteStoreRequest extends FormRequest
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
            'nombre' => ['required', 'string'],
            'apellidos' => ['required', 'string'],
            'fecha_nacimiento' => ['required', 'date'],
            'curp' => ['nullable', 'string', 'unique:residentes,curp'],
            'diagnostico' => ['nullable', 'string'],
            'alergias' => ['nullable', 'string'],
            'contacto_emergencia' => ['required', 'string'],
            'telefono_emergencia' => ['required', 'string'],
            'foto_path' => ['nullable', 'string'],
        ];
    }
}
