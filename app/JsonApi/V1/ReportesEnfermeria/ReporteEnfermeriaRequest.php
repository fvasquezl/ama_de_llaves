<?php

namespace App\JsonApi\V1\ReportesEnfermeria;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ReporteEnfermeriaRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     */
    public function rules(): array
    {
        $presence = $this->isCreating() ? 'required' : 'sometimes';

        $rondaUnique = Rule::unique('reporte_enfermerias', 'ronda_enfermeria_id');

        if (! $this->isCreating() && $model = $this->model()) {
            $rondaUnique = $rondaUnique->ignore($model->getKey());
        }

        return [
            'rondaEnfermeria' => [$presence, JsonApiRule::toOne()],
            'rondaEnfermeria.id' => ['exists:ronda_enfermerias,id', $rondaUnique],
            'enfermera' => [$presence, JsonApiRule::toOne()],
            'enfermera.id' => ['uuid', 'exists:users,id'],
            // A report cannot be created already signed (spec-part-12); a
            // borrador -> firmado transition is only permitted via update.
            'estado' => [
                'sometimes',
                $this->isCreating() ? Rule::in(['borrador']) : Rule::in(['borrador', 'firmado']),
            ],
            'incidencias' => ['nullable', 'string'],
            'observaciones' => ['nullable', 'string'],
            // Must have a rule to be included in `validated()` at all —
            // otherwise the `firmado_at` auto-stamp injected by
            // `validationData()` below would never reach hydration
            // (`Update`/`Store` actions hydrate from `$request->validated()`,
            // which only contains keys that have a rule).
            'firmado_at' => ['nullable', 'date'],
        ];
    }

    /**
     * Task 6.5 (decision #8): a signed report is fully immutable. This is
     * an explicit early check — deliberately BEFORE normal field
     * validation runs — so any update attempt on a `firmado` report
     * short-circuits with exactly 409 Conflict, not 422, regardless of
     * which fields are being changed.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        parent::prepareForValidation();

        if ($this->isUpdating() && $this->modelOrFail()->estado === 'firmado') {
            throw new ConflictHttpException(
                'Este reporte ya fue firmado y no puede modificarse.',
            );
        }
    }

    /**
     * Auto-stamp `firmado_at` with the current timestamp the moment
     * `estado` transitions to `firmado`, if not explicitly supplied
     * (spec-part-13).
     *
     * @return array
     */
    public function validationData()
    {
        $data = parent::validationData();

        if (($data['estado'] ?? null) === 'firmado' && empty($data['firmado_at'])) {
            $data['firmado_at'] = now()->toIso8601String();
        }

        return $data;
    }
}
