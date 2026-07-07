<?php

namespace App\JsonApi\V1\Habitaciones;

use App\Models\Habitacion;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Schema;

/**
 * Minimal schema exposing `Habitacion` as a JSON:API resource so that
 * `visitas-habitacion`'s `habitacion` relationship (`BelongsTo`) can be
 * submitted, validated, and extracted. See `UserSchema` for the full
 * rationale — same class of gap. No HTTP routes are mounted for this
 * resource type; it exists solely as a relationship target in this
 * change's scope. A full `Habitacion` JSON:API resource is a separate
 * concern outside enfermeria-api.
 */
class HabitacionSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = Habitacion::class;

    /**
     * No HTTP routes are mounted for this resource type, so a self link
     * would point to a URL that 404s if followed.
     */
    protected bool $selfLink = false;

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
            Str::make('numero')->readOnly(),
            Str::make('tipo')->readOnly(),
            Str::make('estado')->readOnly(),
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
        ];
    }
}
