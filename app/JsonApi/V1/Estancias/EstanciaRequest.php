<?php

namespace App\JsonApi\V1\Estancias;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class EstanciaRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     */
    public function rules(): array
    {
        $presence = $this->isCreating() ? 'required' : 'sometimes';

        return [
            'residente' => [$presence, JsonApiRule::toOne()],
            'residente.id' => ['exists:residentes,id'],
            'habitacion' => [$presence, JsonApiRule::toOne()],
            'habitacion.id' => ['exists:habitacions,id'],
            'fecha_ingreso' => ['required', 'date'],
            'fecha_egreso' => ['nullable', 'date', 'after_or_equal:fecha_ingreso'],
            'estado' => ['sometimes', Rule::in(['activa', 'alta', 'traslado', 'fallecimiento'])],
            'notas_medicas' => ['nullable', 'string'],
        ];
    }
}
