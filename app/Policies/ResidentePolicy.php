<?php

namespace App\Policies;

use App\Models\Residente;
use App\Models\User;

/**
 * Flat/permission-only policy — no row-level ownership concept applies to
 * `Residente` (spec-part-08). Any user holding the relevant permission may
 * act on any residente.
 */
class ResidentePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('residentes.ver');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Residente $residente): bool
    {
        return $user->can('residentes.ver');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('residentes.crear');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Residente $residente): bool
    {
        return $user->can('residentes.editar');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * Deletion is a soft delete (the model uses `SoftDeletes`).
     */
    public function delete(User $user, Residente $residente): bool
    {
        return $user->can('residentes.eliminar');
    }
}
