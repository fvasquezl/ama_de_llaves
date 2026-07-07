<?php

namespace App\JsonApi\V1\Concerns;

use Illuminate\Http\Request;

/**
 * Shared row-level ownership scoping for the enfermeria domain.
 *
 * All 5 enfermeria JSON:API resources are ultimately owned by whichever
 * `User` holds the `enfermera` role for the relevant `RondaEnfermeria`
 * (`RondaEnfermeria.enfermera_id`, walked through foreign keys for the
 * other 4 resources). Users holding `supervisor`/`admin` roles, or the
 * `is_super_admin` flag, are never subject to this scoping — they are
 * governed solely by the standard `{resource}.ver`/`.crear`/`.editar`
 * permission checks (see spec-part-18, "Elevated Roles Bypass Ownership
 * Scoping").
 *
 * This trait is applied inside `Schema::indexQuery()` implementations,
 * which is the laravel-json-api/eloquent v5.2 hook for row-level query
 * scoping on the `index` action (verified against
 * `vendor/laravel-json-api/eloquent/src/Schema.php` and `QueryAll.php`).
 * It intentionally does NOT apply to `show`/`update` — those are scoped
 * by the resource's Policy `view`/`update` methods instead, which
 * correctly surface `403 Forbidden` rather than an empty/`404` result
 * for cross-tenant access (see `QueryOne.php`, which never calls
 * `indexQuery()`).
 */
trait ScopesToOwnEnfermera
{
    /**
     * Determine whether the current request must be scoped down to only
     * the records owned by the authenticated enfermera.
     */
    protected function mustScopeToOwnEnfermera(?Request $request): bool
    {
        $user = $request?->user();

        if (! $user) {
            return false;
        }

        if ($user->is_super_admin) {
            return false;
        }

        return ! $user->hasAnyRole(['supervisor', 'admin']);
    }
}
