<?php

namespace App\Policies;

use App\Models\ChecklistItem;
use App\Models\User;

/**
 * Ownership-scoped policy, two-hop (spec-part-14). `ChecklistItem` has no
 * `camarera_id` of its own — ownership is resolved via
 * `tareaLimpieza.camarera_id`. Row-level scoping to the camarera's own
 * items for `index` happens at the Schema/query layer
 * (`ChecklistItemSchema::indexQuery()`), not here.
 */
class ChecklistItemPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('checklist-limpieza.ver');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ChecklistItem $checklistItem): bool
    {
        return $user->can('checklist-limpieza.ver')
            && ($user->hasAnyRole(['supervisor', 'admin']) || $checklistItem->tareaLimpieza->camarera_id === $user->id);
    }

    /**
     * Determine whether the user can create models.
     *
     * Only the permission is checked here: at create time there is no
     * persisted model to resolve ownership from, since the item does not
     * exist yet. Cross-tenant validation of the target `tarea_limpieza_id`
     * (i.e. that it belongs to the requesting camarera's own task) is
     * enforced at the Request layer, not in this Policy.
     */
    public function create(User $user): bool
    {
        return $user->can('checklist-limpieza.crear');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ChecklistItem $checklistItem): bool
    {
        return $user->can('checklist-limpieza.editar')
            && ($user->hasAnyRole(['supervisor', 'admin']) || $checklistItem->tareaLimpieza->camarera_id === $user->id);
    }
}
