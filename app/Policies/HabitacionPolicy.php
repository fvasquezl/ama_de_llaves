<?php

namespace App\Policies;

use App\Models\Habitacion;
use App\Models\User;

/**
 * Flat/permission-only policy — no row-level ownership concept applies to
 * `Habitacion` (spec-part-05). Any user holding the relevant permission may
 * act on any habitacion.
 */
class HabitacionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('habitaciones.ver');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Habitacion $habitacion): bool
    {
        return $user->can('habitaciones.ver');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('habitaciones.crear');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Habitacion $habitacion): bool
    {
        return $user->can('habitaciones.editar');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * Deletion is a soft delete (the model uses `SoftDeletes`).
     */
    public function delete(User $user, Habitacion $habitacion): bool
    {
        return $user->can('habitaciones.eliminar');
    }
}
