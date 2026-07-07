<?php

namespace App\Policies;

use App\Models\ReporteEnfermeria;
use App\Models\User;

class ReporteEnfermeriaPolicy
{
    /**
     * Determine whether the user can view any models.
     *
     * Row-level scoping to the enfermera's own reportes happens at the
     * Schema/query layer (index filtering), not here.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('reportes-enfermeria.ver');
    }

    /**
     * Determine whether the user can view the model.
     *
     * Ownership is resolved directly from `enfermera_id` on the report
     * itself (not via the parent ronda) — a report is authored by whichever
     * enfermera files it, which is normally but not necessarily the same
     * enfermera who owns the ronda it reports on.
     */
    public function view(User $user, ReporteEnfermeria $reporteEnfermeria): bool
    {
        return $user->can('reportes-enfermeria.ver')
            && ($user->hasAnyRole(['supervisor', 'admin']) || $reporteEnfermeria->enfermera_id === $user->id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('reportes-enfermeria.crear');
    }

    /**
     * Determine whether the user can update the model.
     *
     * Permission and ownership only. Immutability of signed reports
     * (`estado === 'firmado'`) is enforced at the Schema/Action layer as a
     * 409 Conflict, not here — a Policy denial surfaces as 403, which would
     * be the wrong semantics for "this exists and you may act on this type
     * of resource, but not in its current state".
     */
    public function update(User $user, ReporteEnfermeria $reporteEnfermeria): bool
    {
        return $user->can('reportes-enfermeria.editar')
            && ($user->hasAnyRole(['supervisor', 'admin']) || $reporteEnfermeria->enfermera_id === $user->id);
    }
}
