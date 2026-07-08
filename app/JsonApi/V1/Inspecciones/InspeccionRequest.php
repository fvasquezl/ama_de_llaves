<?php

namespace App\JsonApi\V1\Inspecciones;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

/**
 * Store-only in practice (no update route is registered — Inspeccion is
 * immutable, spec-part-15), but written as a normal `isCreating()`-
 * branching class for consistency with every other Request in this app.
 */
class InspeccionRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     */
    public function rules(): array
    {
        $presence = $this->isCreating() ? 'required' : 'sometimes';

        return [
            'tareaLimpieza' => [$presence, JsonApiRule::toOne()],
            'tareaLimpieza.id' => ['exists:tarea_limpiezas,id'],
            'supervisora' => [$presence, JsonApiRule::toOne()],
            'supervisora.id' => ['uuid', 'exists:users,id'],
            'resultado' => ['required', Rule::in(['aprobada', 'rechazada'])],
            'puntaje' => ['nullable', 'integer', 'min:0', 'max:100'],
            'notas' => ['nullable', 'string'],
        ];
    }
}
