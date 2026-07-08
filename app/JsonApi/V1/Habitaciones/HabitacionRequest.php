<?php

namespace App\JsonApi\V1\Habitaciones;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class HabitacionRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     */
    public function rules(): array
    {
        $presence = $this->isCreating() ? 'required' : 'sometimes';

        return [
            'sucursal' => [$presence, JsonApiRule::toOne()],
            'sucursal.id' => ['exists:sucursals,id'],
            'numero' => ['required', 'string'],
            'tipo' => ['required', Rule::in(['individual', 'doble', 'suite'])],
            'piso' => ['required', 'integer'],
            'capacidad' => ['sometimes', 'integer', 'min:1'],
            'estado' => ['sometimes', Rule::in([
                'disponible', 'ocupada', 'sucia', 'en_limpieza', 'limpia', 'inspeccionada', 'fuera_de_servicio',
            ])],
            'nfc_tag_uid' => [
                'nullable',
                'string',
                Rule::unique('habitacions', 'nfc_tag_uid')->ignore($this->model()?->getKey()),
            ],
            'notas' => ['nullable', 'string'],
        ];
    }
}
