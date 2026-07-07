<?php

namespace App\JsonApi\V1\AlertasRonda;

use App\JsonApi\V1\Concerns\ScopesToOwnEnfermera;
use App\Models\AlertaRonda;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class AlertaRondaSchema extends Schema
{
    use ScopesToOwnEnfermera;

    /**
     * The model the schema corresponds to.
     */
    public static string $model = AlertaRonda::class;

    /**
     * {@inheritDoc}
     */
    protected ?array $defaultPagination = ['number' => 1, 'size' => 15];

    /**
     * {@inheritDoc}
     */
    public static function type(): string
    {
        return 'alertas-ronda';
    }

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),
            // `rondaEnfermeria` and `visitaHabitacion` are system-assigned
            // at generation time and immutable thereafter (no store route
            // exists for this resource at all — see spec-part-14/15).
            BelongsTo::make('rondaEnfermeria')->type('rondas-enfermeria')->readOnly(),
            BelongsTo::make('visitaHabitacion')->type('visitas-habitacion')->readOnly(),
            // `tipo` is system-generated and immutable — see spec-part-15.
            Str::make('tipo')->sortable()->readOnly(),
            Boolean::make('atendido')->sortable(),
            // Server-controlled: the Request/Action layer (Phase 6, task
            // 6.7) forces this to the authenticated user's id whenever
            // `atendido` transitions to true, ignoring any client-supplied
            // value, per spec-part-16. Left writable here so that layer
            // can fill it; the Schema does not enforce the override.
            BelongsTo::make('atendidoPor')->type('users'),
            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('atendido')->asBoolean(),
            Where::make('tipo'),
        ];
    }

    /**
     * Get the resource paginator.
     */
    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    /**
     * Scope the index query to alerts belonging to the authenticated
     * enfermera's own rounds, unless she holds an elevated role.
     * See spec-part-14.
     */
    public function indexQuery(?Request $request, Builder $query): Builder
    {
        if ($this->mustScopeToOwnEnfermera($request)) {
            $query->whereHas(
                'rondaEnfermeria',
                fn (Builder $q) => $q->where('enfermera_id', $request->user()->id),
            );
        }

        return $query;
    }
}
