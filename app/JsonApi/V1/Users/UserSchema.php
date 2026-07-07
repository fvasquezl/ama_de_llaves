<?php

namespace App\JsonApi\V1\Users;

use App\Models\User;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Schema;

/**
 * Minimal schema exposing `User` as a JSON:API resource so that the
 * enfermeria-api resources' `enfermera`/`atendidoPor` relationships (both
 * `BelongsTo` fields pointing at `users`) can be submitted, validated, and
 * extracted. Without ANY schema registered for `users`, the JSON:API spec
 * compliance layer rejects `{"type": "users", ...}` relationship
 * identifiers outright with a 400 "Resource type users is not recognised",
 * and `ResourceRequest::dataForUpdate()` throws a LogicException when it
 * tries to resolve the inverse schema during update-extraction — this
 * blocks `store`/`update` entirely for `rondas-enfermeria`,
 * `reportes-enfermeria`, and `alertas-ronda` (confirmed empirically).
 *
 * No HTTP routes are mounted for this resource type (see routes/api.php)
 * — it exists solely as a relationship target, not as a first-class
 * browsable/manageable resource in this change's scope. A full `User`
 * JSON:API resource (with its own routes, filters, Policy-gated
 * authorization, etc.) is a separate concern outside enfermeria-api.
 */
class UserSchema extends Schema
{
    /**
     * The model the schema corresponds to.
     */
    public static string $model = User::class;

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
        return 'users';
    }

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            // `->uuid()`: `User` uses UUID primary keys (`HasUuids`), not
            // the default auto-increment integer pattern that `ID::make()`
            // assumes. Without this, `Repository::find()` short-circuits
            // to null for any UUID resource id before even querying,
            // because `ID::match()` rejects it against the default
            // `[0-9]+` pattern (confirmed empirically).
            ID::make()->uuid(),
            Str::make('name')->readOnly(),
            Str::make('email')->readOnly(),
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
