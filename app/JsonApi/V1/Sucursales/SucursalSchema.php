<?php

namespace App\JsonApi\V1\Sucursales;

use App\Models\Sucursal;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

/**
 * Flat/permission-only resource — no row-level ownership scoping applies
 * (spec-part-03). Authorization is entirely delegated to `SucursalPolicy`.
 */
class SucursalSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = Sucursal::class;

    /**
     * {@inheritDoc}
     */
    protected ?array $defaultPagination = ['number' => 1, 'size' => 15];

    /**
     * {@inheritDoc}
     */
    public static function type(): string
    {
        return 'sucursales';
    }

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('nombre')->sortable(),
            Str::make('direccion'),
            Str::make('ciudad')->sortable(),
            Str::make('telefono'),
            Str::make('email'),
            Boolean::make('activa')->sortable(),
            HasMany::make('habitacions')->type('habitacions'),
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
            Where::make('ciudad'),
            Where::make('activa')->asBoolean(),
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
