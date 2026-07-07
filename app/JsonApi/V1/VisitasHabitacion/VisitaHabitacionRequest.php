<?php

namespace App\JsonApi\V1\VisitasHabitacion;

use App\Models\RondaEnfermeria;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class VisitaHabitacionRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     */
    public function rules(): array
    {
        $presence = $this->isCreating() ? 'required' : 'sometimes';

        return [
            'rondaEnfermeria' => [$presence, JsonApiRule::toOne()],
            'rondaEnfermeria.id' => ['exists:ronda_enfermerias,id'],
            'habitacion' => [$presence, JsonApiRule::toOne()],
            'habitacion.id' => ['exists:habitacions,id'],
            'residente' => [$presence, JsonApiRule::toOne()],
            'residente.id' => ['exists:residentes,id'],
            'hora_programada' => ['required', 'date_format:H:i:s'],
            'estado' => ['sometimes', Rule::in(['pendiente', 'en_progreso', 'completada', 'omitida'])],
            'nfc_verificado' => ['sometimes', 'boolean'],
            'nfc_escaneado_at' => ['nullable', 'date'],
            'notas' => ['nullable', 'string'],
        ];
    }

    /**
     * Auto-populate `nfc_escaneado_at` with the current timestamp when the
     * client marks `nfc_verificado` as `true` without supplying it
     * (spec-part-08). This runs on the final MERGED data (existing values
     * overlaid with the submission), so it correctly handles both create
     * and partial-update requests.
     *
     * @return array
     */
    public function validationData()
    {
        $data = parent::validationData();

        if (($data['nfc_verificado'] ?? false) === true && empty($data['nfc_escaneado_at'])) {
            $data['nfc_escaneado_at'] = now()->toIso8601String();
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->assertOwnsParentRonda($validator);
            $this->assertNfcConsistency($validator);
        });
    }

    /**
     * Task 6.3: an enfermera may only create/move a visit under a round
     * she owns. Supervisors/admins/super-admins are exempt (spec-part-06,
     * spec-part-18).
     */
    private function assertOwnsParentRonda(Validator $validator): void
    {
        $user = $this->user();

        if (! $user || $user->hasAnyRole(['supervisor', 'admin']) || $user->is_super_admin) {
            return;
        }

        $rondaId = $validator->getValue('rondaEnfermeria.id');

        if (! $rondaId) {
            return;
        }

        $ronda = RondaEnfermeria::find($rondaId);

        if ($ronda && (string) $ronda->enfermera_id !== (string) $user->id) {
            $validator->errors()->add(
                'rondaEnfermeria',
                'No puedes gestionar visitas bajo una ronda que no te pertenece.',
            );
        }
    }

    /**
     * Spec-part-08: the system MUST NOT accept a request that sets
     * `nfc_verificado` to `false` while also supplying `nfc_escaneado_at`.
     * The "must be present when true" half of the rule is satisfied by the
     * `validationData()` auto-population above, so by the time this runs
     * the only remaining invalid state to reject is false+present.
     */
    private function assertNfcConsistency(Validator $validator): void
    {
        $data = $validator->getData();
        $verificado = (bool) ($data['nfc_verificado'] ?? false);
        $escaneadoAt = $data['nfc_escaneado_at'] ?? null;

        if (! $verificado && $escaneadoAt !== null) {
            $validator->errors()->add(
                'nfc_escaneado_at',
                'No se puede establecer nfc_escaneado_at cuando nfc_verificado es false.',
            );
        }
    }
}
