<?php

namespace App\JsonApi\V1\Residentes;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class ResidenteRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     */
    public function rules(): array
    {
        $presence = $this->isCreating() ? 'required' : 'sometimes';

        return [
            'nombre' => [$presence, 'string'],
            'apellidos' => [$presence, 'string'],
            'fecha_nacimiento' => [$presence, 'date', 'before:today'],
            'curp' => [
                'nullable',
                'string',
                Rule::unique('residentes', 'curp')->ignore($this->model()?->getKey()),
            ],
            'diagnostico' => ['nullable', 'string'],
            'alergias' => ['nullable', 'string'],
            'contacto_emergencia' => [$presence, 'string'],
            'telefono_emergencia' => [$presence, 'string'],
            'foto_path' => ['nullable', 'string'],
        ];
    }
}
