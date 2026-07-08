<?php

namespace App\JsonApi\V1\TareasLimpieza;

use App\JsonApi\V1\Concerns\ScopesToOwnUser;
use App\Models\TareaLimpieza;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

/**
 * Ownership-scoped resource (owner FK: `camarera_id`, direct — spec-part-11).
 * A non-elevated camarera only sees/manages `TareaLimpieza` records where
 * `camarera_id` equals her own id; `supervisor`/`admin` see and manage all
 * records, subject to permission. A `null` `camarera_id` (unassigned task)
 * never equals the requesting user's id, so it is correctly excluded from a
 * camarera's index and denied on `show`/`update` (spec-part-11's nullable-
 * owner edge case) — `view`/`update` authorization itself lives in
 * `TareaLimpiezaPolicy`.
 *
 * No `destroy` route is registered (decision 1 — `.eliminar` dropped
 * entirely, no soft-deletes column).
 */
class TareaLimpiezaSchema extends Schema
{
    use ScopesToOwnUser;

    /**
     * The model the schema corresponds to.
     */
    public static string $model = TareaLimpieza::class;

    /**
     * {@inheritDoc}
     */
    protected ?array $defaultPagination = ['number' => 1, 'size' => 15];

    /**
     * {@inheritDoc}
     */
    public static function type(): string
    {
        return 'tareas-limpieza';
    }

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),
            BelongsTo::make('habitacion')->type('habitacions'),
            BelongsTo::make('camarera')->type('users'),
            BelongsTo::make('supervisora')->type('users'),
            Str::make('tipo')->sortable(),
            Str::make('prioridad')->sortable(),
            Str::make('estado')->sortable(),
            Str::make('fecha_programada')->sortable(),
            Str::make('hora_inicio'),
            Str::make('hora_fin'),
            Str::make('notas'),
            HasMany::make('checklistItems')->type('checklist-limpieza'),
            HasMany::make('inspeccions')->type('inspecciones'),
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
            Where::make('tipo'),
            Where::make('prioridad'),
            Where::make('habitacion_id'),
            Where::make('camarera_id'),
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
     * Scope the index query to the authenticated camarera's own tasks
     * unless she holds an elevated role. See spec-part-11.
     */
    public function indexQuery(?Request $request, Builder $query): Builder
    {
        if ($this->mustScopeToOwnUser($request)) {
            $query->where('camarera_id', $request->user()->id);
        }

        return $query;
    }
}
