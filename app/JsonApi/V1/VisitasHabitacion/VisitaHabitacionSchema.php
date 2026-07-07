<?php

namespace App\JsonApi\V1\VisitasHabitacion;

use App\JsonApi\V1\Concerns\ScopesToOwnEnfermera;
use App\Models\VisitaHabitacion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class VisitaHabitacionSchema extends Schema
{
    use ScopesToOwnEnfermera;

    /**
     * The model the schema corresponds to.
     */
    public static string $model = VisitaHabitacion::class;

    /**
     * {@inheritDoc}
     */
    protected ?array $defaultPagination = ['number' => 1, 'size' => 15];

    /**
     * {@inheritDoc}
     */
    public static function type(): string
    {
        return 'visitas-habitacion';
    }

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),
            BelongsTo::make('rondaEnfermeria')->type('rondas-enfermeria'),
            BelongsTo::make('habitacion')->type('habitacions'),
            BelongsTo::make('residente')->type('residentes'),
            Str::make('hora_programada'),
            Boolean::make('nfc_verificado'),
            DateTime::make('nfc_escaneado_at'),
            Str::make('estado')->sortable(),
            Str::make('notas'),
            HasMany::make('checklistEnfermeriaItems')->type('checklist-enfermeria'),
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
            Where::make('estado'),
            Where::make('nfc_verificado')->asBoolean(),
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
     * Scope the index query to visits under the authenticated enfermera's
     * own rounds unless she holds an elevated role. See spec-part-06.
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
