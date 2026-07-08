<?php

namespace App\JsonApi\V1\Estancias;

use App\Models\Estancia;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

/**
 * Flat/permission-only resource — no row-level ownership scoping applies
 * (spec-part-09). Authorization is entirely delegated to `EstanciaPolicy`.
 *
 * No `destroy` route is registered: the model has no soft-deletes column and
 * the legacy controller never had one either (spec-part-08 — unchanged, not
 * newly restricted).
 */
class EstanciaSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = Estancia::class;

    /**
     * {@inheritDoc}
     */
    protected ?array $defaultPagination = ['number' => 1, 'size' => 15];

    /**
     * {@inheritDoc}
     */
    public static function type(): string
    {
        return 'estancias';
    }

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),
            BelongsTo::make('residente')->type('residentes'),
            BelongsTo::make('habitacion')->type('habitacions'),
            Str::make('fecha_ingreso')->sortable(),
            Str::make('fecha_egreso'),
            Str::make('estado')->sortable(),
            Str::make('notas_medicas'),
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
            Where::make('residente_id'),
            Where::make('habitacion_id'),
        ];
    }

    /**
     * Get the resource paginator.
     */
    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }
}
