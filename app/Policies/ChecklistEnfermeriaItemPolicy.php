<?php

namespace App\Policies;

use App\Models\ChecklistEnfermeriaItem;
use App\Models\User;

class ChecklistEnfermeriaItemPolicy
{
    /**
     * Determine whether the user can view any models.
     *
     * Row-level scoping to the enfermera's own checklist items happens at
     * the Schema/query layer (index filtering), not here.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('checklist-enfermeria.ver');
    }

    /**
     * Determine whether the user can view the model.
     *
     * Ownership is resolved two hops up: `visitaHabitacion.rondaEnfermeria.enfermera_id`
     * (`ChecklistEnfermeriaItem` has no `enfermera_id` of its own).
     */
    public function view(User $user, ChecklistEnfermeriaItem $checklistEnfermeriaItem): bool
    {
        return $user->can('checklist-enfermeria.ver')
            && ($user->hasAnyRole(['supervisor', 'admin'])
                || $checklistEnfermeriaItem->visitaHabitacion->rondaEnfermeria->enfermera_id === $user->id);
    }

    /**
     * Determine whether the user can create models.
     *
     * Only the permission is checked here: at create time there is no
     * persisted model to resolve ownership from, since the item does not
     * exist yet. Cross-tenant validation of the target `visita_habitacion_id`
     * (i.e. that it belongs to the requesting enfermera's own ronda) is
     * enforced at the Request/Action layer, not in this Policy.
     */
    public function create(User $user): bool
    {
        return $user->can('checklist-enfermeria.crear');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ChecklistEnfermeriaItem $checklistEnfermeriaItem): bool
    {
        return $user->can('checklist-enfermeria.editar')
            && ($user->hasAnyRole(['supervisor', 'admin'])
                || $checklistEnfermeriaItem->visitaHabitacion->rondaEnfermeria->enfermera_id === $user->id);
    }
}
