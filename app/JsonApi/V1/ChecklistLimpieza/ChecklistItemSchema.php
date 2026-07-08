<?php

namespace App\JsonApi\V1\ChecklistLimpieza;

use App\JsonApi\V1\Concerns\ScopesToOwnUser;
use App\Models\ChecklistItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\Boolean;
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
 * Ownership-scoped resource, two-hop (spec-part-14): the item itself has no
 * owner FK, so ownership is resolved via `tareaLimpieza.camarera_id`. A
 * non-elevated camarera only sees/manages items whose parent task she owns;
 * `supervisor`/`admin` see and manage all items, subject to permission.
 *
 * Gains a `show` route versus the legacy controller (locked-in judgment
 * call, spec-part-12) — index/store/show/update, no destroy (decision 1 —
 * `.eliminar` dropped entirely, no soft-deletes column).
 */
class ChecklistItemSchema extends Schema
{
    use ScopesToOwnUser;

    /**
     * The model the schema corresponds to.
     */
    public static string $model = ChecklistItem::class;

    /**
     * {@inheritDoc}
     */
    protected ?array $defaultPagination = ['number' => 1, 'size' => 15];

    /**
     * {@inheritDoc}
     */
    public static function type(): string
    {
        return 'checklist-limpieza';
    }

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),
            BelongsTo::make('tareaLimpieza')->type('tareas-limpieza'),
            Str::make('descripcion'),
            Boolean::make('completado')->sortable(),
            Number::make('orden')->sortable(),
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
            Where::make('completado')->asBoolean(),
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
     * Scope the index query to checklist items whose two-hop ownership
     * chain (`tareaLimpieza.camarera_id`) resolves to the authenticated
     * user, unless she holds an elevated role. See spec-part-14.
     */
    public function indexQuery(?Request $request, Builder $query): Builder
    {
        if ($this->mustScopeToOwnUser($request)) {
            $query->whereHas(
                'tareaLimpieza',
                fn (Builder $q) => $q->where('camarera_id', $request->user()->id),
            );
        }

        return $query;
    }
}
