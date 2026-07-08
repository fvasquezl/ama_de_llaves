<?php

namespace App\JsonApi\V1\Concerns;

use Illuminate\Http\Request;

/**
 * Shared row-level ownership scoping for the 4 worker-owned domains
 * (TareaLimpieza, ChecklistItem, Inspeccion, ReporteMantenimiento).
 * Mirrors ScopesToOwnEnfermera's shape exactly: this trait ONLY answers
 * "must this request be scoped at all" (elevated-role bypass check). It
 * intentionally does NOT know the owning column/relation path, which
 * differs per domain (direct FK vs ChecklistItem's 2-hop
 * tareaLimpieza.camarera_id) — each Schema's indexQuery() writes its own
 * one-line where()/whereHas(), exactly like ChecklistEnfermeriaItemSchema
 * already does on top of ScopesToOwnEnfermera. Applied only in
 * indexQuery(); show/update are scoped by the resource's Policy instead
 * (403, not an empty/404 index result) — see ScopesToOwnEnfermera's
 * docblock for the same rationale, verified against the same
 * QueryAll.php / QueryOne.php package internals.
 */
trait ScopesToOwnUser
{
    /**
     * Determine whether the current request must be scoped down to only
     * the records owned by the authenticated user.
     */
    protected function mustScopeToOwnUser(?Request $request): bool
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
