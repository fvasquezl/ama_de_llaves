<?php

namespace App\JsonApi\V1\RondasEnfermeria;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class RondaEnfermeriaRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     */
    public function rules(): array
    {
        // Required on create (spec-part-04); merely `sometimes` on update
        // since JSON:API PATCH is a partial update and re-assigning
        // ownership of an existing round is not a supported operation.
        $enfermeraPresence = $this->isCreating() ? 'required' : 'sometimes';

        return [
            'enfermera' => [$enfermeraPresence, JsonApiRule::toOne()],
            'enfermera.id' => ['uuid', 'exists:users,id'],
            'turno' => ['required', Rule::in(['matutino', 'vespertino', 'nocturno'])],
            'fecha' => ['required', 'date'],
            'hora_inicio_programada' => ['required', 'date_format:H:i:s'],
            'hora_fin_programada' => ['required', 'date_format:H:i:s', 'after:hora_inicio_programada'],
            'hora_inicio_real' => ['nullable', 'date_format:H:i:s', 'required_with:hora_fin_real'],
            'hora_fin_real' => ['nullable', 'date_format:H:i:s', 'after:hora_inicio_real'],
            'estado' => ['sometimes', Rule::in(['pendiente', 'en_curso', 'completada', 'incompleta'])],
            'notas' => ['nullable', 'string'],
        ];
    }
}
