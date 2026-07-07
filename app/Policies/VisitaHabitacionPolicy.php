<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VisitaHabitacion;

class VisitaHabitacionPolicy
{
    /**
     * Determine whether the user can view any models.
     *
     * Row-level scoping to the enfermera's own visitas happens at the
     * Schema/query layer (index filtering), not here.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('visitas-habitacion.ver');
    }

    /**
     * Determine whether the user can view the model.
     *
     * Ownership is resolved one hop up through the parent ronda's
     * `enfermera_id` (`VisitaHabitacion` has no `enfermera_id` of its own).
     */
    public function view(User $user, VisitaHabitacion $visitaHabitacion): bool
    {
        return $user->can('visitas-habitacion.ver')
            && ($user->hasAnyRole(['supervisor', 'admin']) || $visitaHabitacion->rondaEnfermeria->enfermera_id === $user->id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('visitas-habitacion.crear');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, VisitaHabitacion $visitaHabitacion): bool
    {
        return $user->can('visitas-habitacion.editar')
            && ($user->hasAnyRole(['supervisor', 'admin']) || $visitaHabitacion->rondaEnfermeria->enfermera_id === $user->id);
    }
}
