<?php

namespace App\JsonApi\V1\ChecklistEnfermeria;

use App\JsonApi\V1\Concerns\ScopesToOwnEnfermera;
use App\Models\ChecklistEnfermeriaItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class ChecklistEnfermeriaItemSchema extends Schema
{
    use ScopesToOwnEnfermera;

    /**
     * The model the schema corresponds to.
     */
    public static string $model = ChecklistEnfermeriaItem::class;

    /**
     * {@inheritDoc}
     */
    protected ?array $defaultPagination = ['number' => 1, 'size' => 15];

    /**
     * {@inheritDoc}
     */
    public static function type(): string
    {
        return 'checklist-enfermeria';
    }

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),
            BelongsTo::make('visitaHabitacion')->type('visitas-habitacion'),
            Str::make('descripcion'),
            Boolean::make('completado')->sortable(),
            Str::make('valor'),
            Number::make('orden')->sortable(),
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
            Where::make('completado')->asBoolean(),
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
     * Scope the index query to checklist items whose two-hop ownership
     * chain (`visitaHabitacion.rondaEnfermeria.enfermera_id`) resolves to
     * the authenticated enfermera, unless she holds an elevated role.
     * See spec-part-09.
     */
    public function indexQuery(?Request $request, Builder $query): Builder
    {
        if ($this->mustScopeToOwnEnfermera($request)) {
            $query->whereHas(
                'visitaHabitacion.rondaEnfermeria',
                fn (Builder $q) => $q->where('enfermera_id', $request->user()->id),
            );
        }

        return $query;
    }
}
