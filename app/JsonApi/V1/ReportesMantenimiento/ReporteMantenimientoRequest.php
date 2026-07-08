<?php

namespace App\JsonApi\V1\ReportesMantenimiento;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class ReporteMantenimientoRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     */
    public function rules(): array
    {
        $presence = $this->isCreating() ? 'required' : 'sometimes';

        return [
            'habitacion' => [$presence, JsonApiRule::toOne()],
            'habitacion.id' => ['exists:habitacions,id'],
            // Client-supplied and validated, not auto-derived from
            // $request->user() — matches the `Inspeccion.supervisora_id`
            // precedent. In practice a camarera reporting her own issue
            // naturally submits her own id, but a supervisor/admin filing
            // on someone else's behalf remains possible.
            'reportadoPor' => [$presence, JsonApiRule::toOne()],
            'reportadoPor.id' => ['uuid', 'exists:users,id'],
            'descripcion' => ['required', 'string'],
            'prioridad' => ['sometimes', Rule::in(['baja', 'normal', 'alta', 'urgente'])],
            'estado' => ['sometimes', Rule::in(['pendiente', 'en_proceso', 'resuelto'])],
            'foto_path' => ['nullable', 'string'],
            'notas_resolucion' => ['nullable', 'string'],
        ];
    }
}
