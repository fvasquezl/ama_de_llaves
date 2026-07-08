<?php

namespace App\JsonApi\V1\Sucursales;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class SucursalRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string'],
            'direccion' => ['required', 'string'],
            'ciudad' => ['required', 'string'],
            'telefono' => ['nullable', 'string'],
            'email' => [
                'nullable',
                'email',
                Rule::unique('sucursals', 'email')->ignore($this->model()?->getKey()),
            ],
            'activa' => ['sometimes', 'boolean'],
        ];
    }
}
