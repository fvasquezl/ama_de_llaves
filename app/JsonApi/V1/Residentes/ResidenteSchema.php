<?php

namespace App\JsonApi\V1\Residentes;

use App\Models\Residente;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Schema;

/**
 * Minimal schema exposing `Residente` as a JSON:API resource so that
 * `visitas-habitacion`'s `residente` relationship (`BelongsTo`) can be
 * submitted, validated, and extracted. See `UserSchema` for the full
 * rationale — same class of gap. No HTTP routes are mounted for this
 * resource type; it exists solely as a relationship target in this
 * change's scope. A full `Residente` JSON:API resource is a separate
 * concern outside enfermeria-api.
 */
class ResidenteSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = Residente::class;

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
        return 'residentes';
    }

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('nombre')->readOnly(),
            Str::make('apellidos')->readOnly(),
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
