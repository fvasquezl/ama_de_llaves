<?php

namespace App\JsonApi\V1\RondasEnfermeria;

use App\JsonApi\V1\Concerns\ScopesToOwnEnfermera;
use App\Models\RondaEnfermeria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Fields\Relations\HasOne;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class RondaEnfermeriaSchema extends Schema
{
    use ScopesToOwnEnfermera;

    /**
     * The model the schema corresponds to.
     */
    public static string $model = RondaEnfermeria::class;

    /**
     * {@inheritDoc}
     */
    protected ?array $defaultPagination = ['number' => 1, 'size' => 15];

    /**
     * {@inheritDoc}
     */
    public static function type(): string
    {
        return 'rondas-enfermeria';
    }

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),
            BelongsTo::make('enfermera')->type('users'),
            Str::make('turno'),
            Str::make('fecha'),
            Str::make('hora_inicio_programada'),
            Str::make('hora_fin_programada'),
            Str::make('hora_inicio_real'),
            Str::make('hora_fin_real'),
            Str::make('estado')->sortable(),
            Str::make('notas'),
            HasMany::make('visitaHabitacions')->type('visitas-habitacion'),
            HasMany::make('alertaRondas')->type('alertas-ronda'),
            HasOne::make('reporteEnfermeria')->type('reportes-enfermeria'),
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
            Where::make('turno'),
            Where::make('fecha_desde', 'fecha')->gte(),
            Where::make('fecha_hasta', 'fecha')->lte(),
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
     * Scope the index query to the authenticated enfermera's own rounds
     * unless she holds an elevated role. See spec-part-03.
     */
    public function indexQuery(?Request $request, Builder $query): Builder
    {
        if ($this->mustScopeToOwnEnfermera($request)) {
            $query->where('enfermera_id', $request->user()->id);
        }

        return $query;
    }
}
