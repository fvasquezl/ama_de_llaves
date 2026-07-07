<?php

namespace App\JsonApi\V1\ChecklistEnfermeria;

use App\Models\VisitaHabitacion;
use Illuminate\Contracts\Validation\Validator;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class ChecklistEnfermeriaItemRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     */
    public function rules(): array
    {
        $presence = $this->isCreating() ? 'required' : 'sometimes';

        return [
            'visitaHabitacion' => [$presence, JsonApiRule::toOne()],
            'visitaHabitacion.id' => ['exists:visita_habitacions,id'],
            'descripcion' => ['required', 'string'],
            'completado' => ['sometimes', 'boolean'],
            'valor' => ['nullable', 'string'],
            'orden' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->assertOwnsParentRonda($validator);
        });
    }

    /**
     * Task 6.4: an enfermera may only add/manage checklist items under a
     * visit whose parent round she owns — the 2-hop chain
     * `visitaHabitacion.rondaEnfermeria.enfermera_id` (spec-part-09).
     * Supervisors/admins/super-admins are exempt.
     */
    private function assertOwnsParentRonda(Validator $validator): void
    {
        $user = $this->user();

        if (! $user || $user->hasAnyRole(['supervisor', 'admin']) || $user->is_super_admin) {
            return;
        }

        $visitaId = $validator->getValue('visitaHabitacion.id');

        if (! $visitaId) {
            return;
        }

        $visita = VisitaHabitacion::with('rondaEnfermeria')->find($visitaId);

        if ($visita && $visita->rondaEnfermeria && (string) $visita->rondaEnfermeria->enfermera_id !== (string) $user->id) {
            $validator->errors()->add(
                'visitaHabitacion',
                'No puedes gestionar items de una visita que no pertenece a tu ronda.',
            );
        }
    }
}
