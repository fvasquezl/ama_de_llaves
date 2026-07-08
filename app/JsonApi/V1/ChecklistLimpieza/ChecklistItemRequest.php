<?php

namespace App\JsonApi\V1\ChecklistLimpieza;

use App\Models\TareaLimpieza;
use Illuminate\Contracts\Validation\Validator;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class ChecklistItemRequest extends ResourceRequest
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
            'descripcion' => ['required', 'string'],
            'completado' => ['sometimes', 'boolean'],
            'orden' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->assertOwnsParentTarea($validator);
        });
    }

    /**
     * Task 8.2: a camarera may only add/manage checklist items under a
     * `TareaLimpieza` she owns — the 2-hop chain
     * `tareaLimpieza.camarera_id` (spec-part-14). Mirrors
     * `ChecklistEnfermeriaItemRequest::assertOwnsParentRonda()` verbatim.
     * Supervisors/admins/super-admins are exempt.
     */
    private function assertOwnsParentTarea(Validator $validator): void
    {
        $user = $this->user();

        if (! $user || $user->hasAnyRole(['supervisor', 'admin']) || $user->is_super_admin) {
            return;
        }

        $tareaId = $validator->getValue('tareaLimpieza.id');

        if (! $tareaId) {
            return;
        }

        $tarea = TareaLimpieza::find($tareaId);

        if ($tarea && (string) $tarea->camarera_id !== (string) $user->id) {
            $validator->errors()->add(
                'tareaLimpieza',
                'No puedes gestionar items de una tarea que no te pertenece.',
            );
        }
    }
}
