<?php

namespace App\JsonApi\V1\TareasLimpieza;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class TareaLimpiezaRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     */
    public function rules(): array
    {
        $habitacionPresence = $this->isCreating() ? 'required' : 'sometimes';

        return [
            'habitacion' => [$habitacionPresence, JsonApiRule::toOne()],
            'habitacion.id' => ['exists:habitacions,id'],
            'camarera' => ['nullable', JsonApiRule::toOne()],
            'camarera.id' => ['uuid', 'exists:users,id'],
            'supervisora' => ['nullable', JsonApiRule::toOne()],
            'supervisora.id' => ['uuid', 'exists:users,id'],
            'tipo' => ['required', Rule::in(['salida', 'estancia', 'profunda', 'llegada'])],
            'prioridad' => ['sometimes', Rule::in(['baja', 'normal', 'alta', 'urgente'])],
            'estado' => ['sometimes', Rule::in(['pendiente', 'en_progreso', 'completada', 'inspeccionada', 'rechazada'])],
            'fecha_programada' => ['required', 'date'],
            'hora_inicio' => ['nullable', 'date_format:H:i:s'],
            'hora_fin' => ['nullable', 'date_format:H:i:s'],
            'notas' => ['nullable', 'string'],
        ];
    }
}
