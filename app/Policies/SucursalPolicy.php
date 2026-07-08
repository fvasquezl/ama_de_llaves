<?php

namespace App\Policies;

use App\Models\Sucursal;
use App\Models\User;

/**
 * Flat/permission-only policy — no row-level ownership concept applies to
 * `Sucursal` (spec-part-03). Any user holding the relevant permission may
 * act on any sucursal.
 */
class SucursalPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('sucursales.ver');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Sucursal $sucursal): bool
    {
        return $user->can('sucursales.ver');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('sucursales.crear');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Sucursal $sucursal): bool
    {
        return $user->can('sucursales.editar');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * Deletion is a soft delete (the model uses `SoftDeletes`).
     */
    public function delete(User $user, Sucursal $sucursal): bool
    {
        return $user->can('sucursales.eliminar');
    }
}
