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
    });
