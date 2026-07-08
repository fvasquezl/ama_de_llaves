<?php

namespace App\JsonApi\V1\ReportesMantenimiento;

use App\JsonApi\V1\Concerns\ScopesToOwnUser;
use App\Models\ReporteMantenimiento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

/**
 * Ownership-scoped resource (owner FK: `reportado_por_id`, direct —
 * spec-part-18). A non-elevated camarera only sees/manages
 * `ReporteMantenimiento` records where `reportado_por_id` equals her own id;
 * `supervisor`/`admin` see and manage all records, subject to permission.
 * This is what makes the new `reportes-mantenimiento.ver` grant to
 * `camarera` (seeder change, spec-part-20) actually useful: she lists only
 * her own filed reports, never others' — `view`/`update` authorization
 * itself lives in `ReporteMantenimientoPolicy`.
 *
 * No `destroy` route is registered (decision 1 — `.eliminar` dropped
 * entirely, no soft-deletes column).
 */
class ReporteMantenimientoSchema extends Schema
{
    use ScopesToOwnUser;

    /**
     * The model the schema corresponds to.
     */
    public static string $model = ReporteMantenimiento::class;

    /**
     * {@inheritDoc}
     */
    protected ?array $defaultPagination = ['number' => 1, 'size' => 15];

    /**
     * {@inheritDoc}
     */
    public static function type(): string
    {
        return 'reportes-mantenimiento';
    }

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),
            BelongsTo::make('habitacion')->type('habitacions'),
            BelongsTo::make('reportadoPor')->type('users'),
            Str::make('descripcion'),
            Str::make('prioridad')->sortable(),
            Str::make('estado')->sortable(),
            Str::make('foto_path'),
            Str::make('notas_resolucion'),
            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),
        ];
    }

    /**
     * Get the resource filters.
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('estado'),
            Where::make('prioridad'),
            Where::make('habitacion_id'),
        ];
    }

    /**
     * Get the resource paginator.
     */
    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    /**
     * Scope the index query to the authenticated user's own filed reports
     * unless she holds an elevated role. See spec-part-18/20.
     */
    public function indexQuery(?Request $request, Builder $query): Builder
    {
        if ($this->mustScopeToOwnUser($request)) {
            $query->where('reportado_por_id', $request->user()->id);
        }

        return $query;
    }
}
