<?php

namespace App\JsonApi\V1\Habitaciones;

use App\Models\Habitacion;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

/**
 * Flat/permission-only resource — no row-level ownership scoping applies
 * (spec-part-05). Authorization is entirely delegated to `HabitacionPolicy`.
 *
 * Expanded in place from the minimal, route-less schema originally built
 * for enfermeria-api (so `VisitaHabitacionSchema`'s `habitacion` relationship
 * could resolve). The resource `type()` and model class are unchanged, so
 * that existing reference continues to work unmodified (spec-part-05
 * non-regression scenario).
 */
class HabitacionSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = Habitacion::class;

    /**
     * {@inheritDoc}
     */
    protected ?array $defaultPagination = ['number' => 1, 'size' => 15];

    /**
     * {@inheritDoc}
     */
    public static function type(): string
    {
        return 'habitacions';
    }

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),
            BelongsTo::make('sucursal')->type('sucursales'),
            Str::make('numero')->sortable(),
            Str::make('tipo')->sortable(),
            Number::make('piso')->sortable(),
            Number::make('capacidad'),
            Str::make('estado')->sortable(),
            Str::make('nfc_tag_uid'),
            Str::make('notas'),
            HasMany::make('estancias')->type('estancias'),
            HasMany::make('tareaLimpiezas')->type('tareas-limpieza'),
            HasMany::make('reporteMantenimientos')->type('reportes-mantenimiento'),
            HasMany::make('visitaHabitacions')->type('visitas-habitacion'),
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
            Where::make('tipo'),
            Where::make('sucursal_id'),
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
