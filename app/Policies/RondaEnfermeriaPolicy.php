<?php

namespace App\Policies;

use App\Models\RondaEnfermeria;
use App\Models\User;

class RondaEnfermeriaPolicy
{
    /**
     * Determine whether the user can view any models.
     *
     * Row-level scoping to the enfermera's own rondas happens at the
     * Schema/query layer (index filtering), not here.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('rondas-enfermeria.ver');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, RondaEnfermeria $rondaEnfermeria): bool
    {
        return $user->can('rondas-enfermeria.ver')
            && ($user->hasAnyRole(['supervisor', 'admin']) || $rondaEnfermeria->enfermera_id === $user->id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('rondas-enfermeria.crear');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RondaEnfermeria $rondaEnfermeria): bool
    {
        return $user->can('rondas-enfermeria.editar')
            && ($user->hasAnyRole(['supervisor', 'admin']) || $rondaEnfermeria->enfermera_id === $user->id);
    }
}
