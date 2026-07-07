<?php

use App\Http\Controllers\ChecklistItemController;
use App\Http\Controllers\EstanciaController;
use App\Http\Controllers\HabitacionController;
use App\Http\Controllers\InspeccionController;
use App\Http\Controllers\ReporteMantenimientoController;
use App\Http\Controllers\ResidenteController;
use App\Http\Controllers\SucursalController;
use App\Http\Controllers\TareaLimpiezaController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::resource('sucursals', SucursalController::class)->except('create', 'edit');

Route::resource('habitacions', HabitacionController::class)->except('create', 'edit');

Route::resource('residentes', ResidenteController::class)->except('create', 'edit');

Route::resource('estancias', EstanciaController::class)->except('create', 'edit', 'destroy');

Route::resource('tarea-limpiezas', TareaLimpiezaController::class)->except('create', 'edit');

Route::resource('checklist-items', ChecklistItemController::class)->except('create', 'edit', 'show');

Route::resource('inspeccions', InspeccionController::class)->only('index', 'store', 'show');

Route::resource('reporte-mantenimientos', ReporteMantenimientoController::class)->except('create', 'edit');
