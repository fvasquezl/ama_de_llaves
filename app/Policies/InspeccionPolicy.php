<?php

namespace App\Policies;

use App\Models\Inspeccion;
use App\Models\User;

/**
 * Ownership-scoped policy (owner FK: `supervisora_id`, direct —
 * spec-part-16). Structurally consistent with the other 3 ownership-scoped
 * policies even though currently inert: only `supervisor`/`admin` hold
 * `inspecciones.crear` today, so the non-elevated branch of `view` is not
 * reachable in practice. No `update`/`delete` methods — Inspeccion is
 * immutable once created (no routes call either).
 */
class InspeccionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('inspecciones.ver');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Inspeccion $inspeccion): bool
    {
        return $user->can('inspecciones.ver')
            && ($user->hasAnyRole(['supervisor', 'admin']) || $inspeccion->supervisora_id === $user->id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('inspecciones.crear');
    }
}
