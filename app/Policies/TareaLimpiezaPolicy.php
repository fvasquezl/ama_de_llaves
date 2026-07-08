<?php

namespace App\Policies;

use App\Models\TareaLimpieza;
use App\Models\User;

/**
 * Ownership-scoped policy (owner FK: `camarera_id`, direct — spec-part-11).
 * Row-level scoping to the camarera's own tasks for `index` happens at the
 * Schema/query layer (`TareaLimpiezaSchema::indexQuery()`), not here.
 */
class TareaLimpiezaPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('tareas-limpieza.ver');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TareaLimpieza $tareaLimpieza): bool
    {
        return $user->can('tareas-limpieza.ver')
            && ($user->hasAnyRole(['supervisor', 'admin']) || $tareaLimpieza->camarera_id === $user->id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('tareas-limpieza.crear');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TareaLimpieza $tareaLimpieza): bool
    {
        return $user->can('tareas-limpieza.editar')
            && ($user->hasAnyRole(['supervisor', 'admin']) || $tareaLimpieza->camarera_id === $user->id);
    }
}
