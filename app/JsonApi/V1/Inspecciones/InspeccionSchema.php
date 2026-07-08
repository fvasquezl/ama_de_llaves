<?php

namespace App\JsonApi\V1\Inspecciones;

use App\JsonApi\V1\Concerns\ScopesToOwnUser;
use App\Models\Inspeccion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

/**
 * Ownership-scoped resource (owner FK: `supervisora_id`, direct —
 * spec-part-16). Structurally consistent with the other 3 ownership-scoped
 * domains even though the non-elevated branch is currently inert: only
 * `supervisor`/`admin` hold `inspecciones.crear` today, so no other role
 * can own an Inspeccion row in practice.
 *
 * Immutable once created: index/show/store only, no update/destroy — the
 * legacy controller never had either action (spec-part-15).
 */
class InspeccionSchema extends Schema
{
    use ScopesToOwnUser;

    /**
     * The model the schema corresponds to.
     */
    public static string $model = Inspeccion::class;

    /**
     * {@inheritDoc}
     */
    protected ?array $defaultPagination = ['number' => 1, 'size' => 15];

    /**
     * {@inheritDoc}
     */
    public static function type(): string
    {
        return 'inspecciones';
    }

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),
            BelongsTo::make('tareaLimpieza')->type('tareas-limpieza'),
            BelongsTo::make('supervisora')->type('users'),
            Str::make('resultado')->sortable(),
            Number::make('puntaje')->sortable(),
            Str::make('notas'),
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
            Where::make('resultado'),
            Where::make('tarea_limpieza_id'),
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
     * Scope the index query to the authenticated supervisora's own
     * inspections unless she holds an elevated role. See spec-part-16.
     */
    public function indexQuery(?Request $request, Builder $query): Builder
    {
        if ($this->mustScopeToOwnUser($request)) {
            $query->where('supervisora_id', $request->user()->id);
        }

        return $query;
    }
}
