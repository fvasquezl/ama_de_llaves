<?php

namespace App\JsonApi\V1\ReportesEnfermeria;

use App\JsonApi\V1\Concerns\ScopesToOwnEnfermera;
use App\Models\ReporteEnfermeria;
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

class ReporteEnfermeriaSchema extends Schema
{
    use ScopesToOwnEnfermera;

    /**
     * The model the schema corresponds to.
     */
    public static string $model = ReporteEnfermeria::class;

    /**
     * {@inheritDoc}
     */
    protected ?array $defaultPagination = ['number' => 1, 'size' => 15];

    /**
     * {@inheritDoc}
     */
    public static function type(): string
    {
        return 'reportes-enfermeria';
    }

    /**
     * Get the resource fields.
     */
    public function fields(): array
    {
        return [
            ID::make(),
            BelongsTo::make('rondaEnfermeria')->type('rondas-enfermeria'),
            BelongsTo::make('enfermera')->type('users'),
            Str::make('incidencias'),
            Str::make('observaciones'),
            DateTime::make('firmado_at'),
            Str::make('estado')->sortable(),
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
     * Scope the index query to reports owned by the authenticated
     * enfermera (`enfermera_id` on the report itself, not the parent
     * round), unless she holds an elevated role. See spec-part-11.
     *
     * Note: the signed-report immutability rule (409 on any update once
     * `estado === 'firmado'`) is enforced at the Request/Action layer in
     * Phase 6, not here.
     */
    public function indexQuery(?Request $request, Builder $query): Builder
    {
        if ($this->mustScopeToOwnEnfermera($request)) {
            $query->where('enfermera_id', $request->user()->id);
        }

        return $query;
    }
}
