<?php

namespace App\Policies;

use App\Models\Estancia;
use App\Models\User;

/**
 * Flat/permission-only policy — no row-level ownership concept applies to
 * `Estancia` (spec-part-09). Any user holding the relevant permission may
 * act on any estancia.
 *
 * No `delete` method is defined: no route calls it (there is no `destroy`
 * action for this resource — spec-part-08), mirroring `RondaEnfermeriaPolicy`'s
 * omission of `delete` for the same reason.
 */
class EstanciaPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('estancias.ver');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Estancia $estancia): bool
    {
        return $user->can('estancias.ver');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('estancias.crear');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Estancia $estancia): bool
    {
        return $user->can('estancias.editar');
    }
}
