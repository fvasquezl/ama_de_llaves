<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReporteMantenimientoStoreRequest extends FormRequest
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
            'habitacion_id' => ['required', 'integer', 'exists:Habitacions,id'],
            'descripcion' => ['required', 'string'],
            'prioridad' => ['required', 'in:baja,normal,alta,urgente'],
            'foto_path' => ['nullable', 'string'],
        ];
    }
}
