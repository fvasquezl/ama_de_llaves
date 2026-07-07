<?php

namespace App\JsonApi\V1\AlertasRonda;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Arr;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class AlertaRondaRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     *
     * There is no `store` route for this resource (system-generated
     * only — spec-part-14), so in practice only `update` ever reaches
     * this class. `tipo`, `rondaEnfermeria`, and `visitaHabitacion` are
     * intentionally NOT listed here: they are marked `->readOnly()` on
     * the Schema (so hydration silently skips them regardless), and the
     * explicit-422-on-change requirement below reads their values
     * straight off the merged validator data, which is populated
     * whether or not a field has a rule.
     */
    public function rules(): array
    {
        return [
            'atendido' => ['sometimes', 'boolean'],
            // Must have a rule to be included in `validated()` at all —
            // otherwise the override injected by `validationData()` below
            // would never reach hydration (see the same pattern/comment
            // in ReporteEnfermeriaRequest for `firmado_at`).
            'atendidoPor' => ['nullable'],
        ];
    }

    /**
     * Task 6.7 (decision #9): whenever `atendido` transitions to (or is
     * submitted as) `true`, the server MUST force `atendidoPor` to the
     * authenticated user's id, unconditionally overriding any
     * client-supplied value, for every role. When `atendido` is `false`,
     * `atendidoPor` MUST be forced to `null`. This runs on the merged
     * data, so it applies consistently regardless of whether `atendido`
     * itself is part of this specific request or just carried over from
     * the record's current state.
     *
     * @return array
     */
    public function validationData()
    {
        $data = parent::validationData();
        $user = $this->user();

        $atendido = (bool) ($data['atendido'] ?? false);

        $data['atendidoPor'] = ($atendido && $user)
            ? ['type' => 'users', 'id' => (string) $user->getAuthIdentifier()]
            : null;

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->assertImmutableFieldsUnchanged($validator);
        });
    }

    /**
     * Task 6.6 (spec-part-15): reject any attempt to change `tipo`,
     * `rondaEnfermeria`, or `visitaHabitacon` on update — these are
     * system-assigned and immutable once the alert exists.
     */
    private function assertImmutableFieldsUnchanged(Validator $validator): void
    {
        if (! $this->isUpdating()) {
            return;
        }

        $model = $this->modelOrFail();
        $data = $validator->getData();

        if (array_key_exists('tipo', $data) && $data['tipo'] !== $model->tipo) {
            $validator->errors()->add('tipo', 'El campo tipo no se puede modificar.');
        }

        $submittedRondaId = Arr::get($data, 'rondaEnfermeria.id');

        if ($submittedRondaId !== null && (string) $submittedRondaId !== (string) $model->ronda_enfermeria_id) {
            $validator->errors()->add('rondaEnfermeria', 'La ronda asociada no se puede modificar.');
        }

        $submittedVisitaId = Arr::get($data, 'visitaHabitacion.id');
        $currentVisitaId = $model->visita_habitacion_id !== null ? (string) $model->visita_habitacion_id : null;

        if (array_key_exists('visitaHabitacion', $data) && (string) $submittedVisitaId !== (string) $currentVisitaId) {
            $validator->errors()->add('visitaHabitacion', 'La visita asociada no se puede modificar.');
        }
    }
}
