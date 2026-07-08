<?php

namespace App\Policies;

use App\Models\ReporteMantenimiento;
use App\Models\User;

/**
 * Ownership-scoped policy (owner FK: `reportado_por_id`, direct —
 * spec-part-18). Row-level scoping to the reporting user's own reports for
 * `index` happens at the Schema/query layer
 * (`ReporteMantenimientoSchema::indexQuery()`), not here.
 */
class ReporteMantenimientoPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('reportes-mantenimiento.ver');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ReporteMantenimiento $reporteMantenimiento): bool
    {
        return $user->can('reportes-mantenimiento.ver')
            && ($user->hasAnyRole(['supervisor', 'admin']) || $reporteMantenimiento->reportado_por_id === $user->id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('reportes-mantenimiento.crear');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ReporteMantenimiento $reporteMantenimiento): bool
    {
        return $user->can('reportes-mantenimiento.editar')
            && ($user->hasAnyRole(['supervisor', 'admin']) || $reporteMantenimiento->reportado_por_id === $user->id);
    }
}
