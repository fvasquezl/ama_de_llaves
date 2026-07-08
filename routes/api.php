<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

JsonApiRoute::server('v1')
    ->prefix('v1')
    ->middleware('auth:api')
    ->resources(function ($server) {
        // Explicit ->parameter() on every resource: the package's default
        // singular/plural inflection mangles these Spanish compound
        // resource-type names (e.g. `rondas-enfermeria` guesses a route
        // parameter of `rondas_enfermerium`) — same class of bug noted in
        // CLAUDE.md for Blueprint's `Route::apiResource()` binding.
        $server->resource('rondas-enfermeria', JsonApiController::class)
            ->only('index', 'show', 'store', 'update')
            ->parameter('ronda_enfermeria');

        $server->resource('visitas-habitacion', JsonApiController::class)
            ->only('index', 'show', 'store', 'update')
            ->parameter('visita_habitacion');

        $server->resource('checklist-enfermeria', JsonApiController::class)
            ->only('index', 'show', 'store', 'update')
            ->parameter('checklist_enfermeria_item');

        $server->resource('reportes-enfermeria', JsonApiController::class)
            ->only('index', 'show', 'store', 'update')
            ->parameter('reporte_enfermeria');

        // System-generated only: no `store` route exists for alertas-ronda.
        $server->resource('alertas-ronda', JsonApiController::class)
            ->only('index', 'show', 'update')
            ->parameter('alerta_ronda');

        // Flat/permission-only; destroy is retained as a soft delete
        // (spec-part-02 — Sucursal was not among the domains where
        // destroy was explicitly dropped).
        $server->resource('sucursales', JsonApiController::class)
            ->only('index', 'show', 'store', 'update', 'destroy')
            ->parameter('sucursal');

        // Flat/permission-only; destroy is retained as a soft delete
        // (spec-part-03 — Habitacion was not among the domains where
        // destroy was explicitly dropped).
        $server->resource('habitacions', JsonApiController::class)
            ->only('index', 'show', 'store', 'update', 'destroy')
            ->parameter('habitacion');

        // Flat/permission-only; destroy is retained as a soft delete
        // (spec-part-06 — Residente was not among the domains where
        // destroy was explicitly dropped).
        $server->resource('residentes', JsonApiController::class)
            ->only('index', 'show', 'store', 'update', 'destroy')
            ->parameter('residente');

        // Flat/permission-only; no destroy route — the model has no
        // soft-deletes column and the legacy controller never had one
        // either (spec-part-08 — unchanged, not newly restricted).
        $server->resource('estancias', JsonApiController::class)
            ->only('index', 'show', 'store', 'update')
            ->parameter('estancia');

        // Ownership-scoped (owner FK: `camarera_id`, direct — spec-part-11);
        // no destroy route — the model has no soft-deletes column and the
        // legacy controller never had one either (decision 1 — `.eliminar`
        // dropped entirely).
        $server->resource('tareas-limpieza', JsonApiController::class)
            ->only('index', 'show', 'store', 'update')
            ->parameter('tarea_limpieza');

        // Ownership-scoped, two-hop via `tareaLimpieza.camarera_id`
        // (spec-part-14); `show` is a deliberate addition versus the
        // legacy controller (locked-in judgment call, spec-part-12); no
        // destroy route (decision 1 — `.eliminar` dropped entirely).
        $server->resource('checklist-limpieza', JsonApiController::class)
            ->only('index', 'show', 'store', 'update')
            ->parameter('checklist_item');

        // Ownership-scoped (owner FK: `supervisora_id`, direct —
        // spec-part-16); immutable once created — no update/destroy route,
        // matching the legacy controller which never had either action
        // (spec-part-15).
        $server->resource('inspecciones', JsonApiController::class)
            ->only('index', 'show', 'store')
            ->parameter('inspeccion');

        // Ownership-scoped (owner FK: `reportado_por_id`, direct —
        // spec-part-18); no destroy route — the model has no soft-deletes
        // column and the legacy controller never had one either (decision 1
        // — `.eliminar` dropped entirely).
        $server->resource('reportes-mantenimiento', JsonApiController::class)
            ->only('index', 'show', 'store', 'update')
            ->parameter('reporte_mantenimiento');
    });
