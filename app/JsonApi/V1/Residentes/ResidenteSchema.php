<?php

namespace App\JsonApi\V1\Residentes;

use App\Models\Residente;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
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
 * (spec-part-08). Authorization is entirely delegated to `ResidentePolicy`.
 * There is no field-level PII restriction: any user holding `residentes.ver`
 * sees every field (curp, diagnostico, alergias, contacto_emergencia,
 * telefono_emergencia included), per the confirmed out-of-scope decision.
 *
 * Expanded in place from the minimal, route-less schema originally built
 * for enfermeria-api (so `VisitaHabitacionSchema`'s `residente` relationship
 * could resolve). The resource `type()` and model class are unchanged, so
 * that existing reference continues to work unmodified (spec-part-07
 * non-regression scenario).
 */
class ResidenteSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = Residente::class;

    /**
     * {@inheritDoc}
     */
    protected ?array $defaultPagination = ['number' => 1, 'size' => 15];

    /**
     * {@inheritDoc}
     */
    public static function type(): string
    {
        return 'residentes';
    }

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('nombre')->sortable(),
            Str::make('apellidos')->sortable(),
            Str::make('fecha_nacimiento'),
            Str::make('curp'),
            Str::make('diagnostico'),
            Str::make('alergias'),
            Str::make('contacto_emergencia'),
            Str::make('telefono_emergencia'),
            Str::make('foto_path'),
            HasMany::make('estancias')->type('estancias'),
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
            Where::make('nombre'),
            Where::make('apellidos'),
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
