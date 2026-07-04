<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TareaLimpiezaUpdateRequest extends FormRequest
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
            'camarera_id' => ['nullable', 'integer', 'exists:users,id'],
            'supervisora_id' => ['nullable', 'integer', 'exists:users,id'],
            'prioridad' => ['required', 'in:baja,normal,alta,urgente'],
            'estado' => ['required', 'in:pendiente,en_progreso,completada,inspeccionada,rechazada'],
            'fecha_programada' => ['required', 'date'],
            'hora_inicio' => ['nullable'],
            'hora_fin' => ['nullable'],
            'notas' => ['nullable', 'string'],
        ];
    }
}
