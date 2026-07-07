<?php

namespace App\Policies;

use App\Models\AlertaRonda;
use App\Models\User;

class AlertaRondaPolicy
{
    /**
     * Determine whether the user can view any models.
     *
     * Row-level scoping to the enfermera's own alertas happens at the
     * Schema/query layer (index filtering), not here.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('alertas-ronda.ver');
    }

    /**
     * Determine whether the user can view the model.
     *
     * Ownership is resolved one hop up through the parent ronda's
     * `enfermera_id` (`AlertaRonda` has no `enfermera_id` of its own —
     * `atendido_por_id` records who resolved the alert, not who owns it).
     */
    public function view(User $user, AlertaRonda $alertaRonda): bool
    {
        return $user->can('alertas-ronda.ver')
            && ($user->hasAnyRole(['supervisor', 'admin']) || $alertaRonda->rondaEnfermeria->enfermera_id === $user->id);
    }

    /**
     * Intentionally no `create` method.
     *
     * Alertas are system-generated only — there is no store route for this
     * resource, so `Gate::inspect('create', ...)` is never invoked. Omitting
     * the method (rather than defining it to always return false) keeps the
     * Policy honest about there being no create action at all.
     */

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AlertaRonda $alertaRonda): bool
    {
        return $user->can('alertas-ronda.editar')
            && ($user->hasAnyRole(['supervisor', 'admin']) || $alertaRonda->rondaEnfermeria->enfermera_id === $user->id);
    }
}
