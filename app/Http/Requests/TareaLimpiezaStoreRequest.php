<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TareaLimpiezaStoreRequest extends FormRequest
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
            'camarera_id' => ['nullable', 'integer', 'exists:users,id'],
            'supervisora_id' => ['nullable', 'integer', 'exists:users,id'],
            'tipo' => ['required', 'in:salida,estancia,profunda,llegada'],
            'prioridad' => ['required', 'in:baja,normal,alta,urgente'],
            'fecha_programada' => ['required', 'date'],
            'notas' => ['nullable', 'string'],
        ];
    }
}
