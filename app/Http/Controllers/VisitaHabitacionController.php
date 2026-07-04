<?php

namespace App\Http\Controllers;

use App\Http\Requests\VisitaHabitacionStoreRequest;
use App\Http\Requests\VisitaHabitacionUpdateRequest;
use App\Models\VisitaHabitacion;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class VisitaHabitacionController extends Controller
{
    public function index(Request $request)
    {
        $visitaHabitacions = VisitaHabitacion::all();

        return $visita_habitaciones;
    }

    public function store(VisitaHabitacionStoreRequest $request): Response
    {
        $visitaHabitacion = VisitaHabitacion::create($request->validated());

        return response()->noContent(201);
    }

    public function show(Request $request, VisitaHabitacion $visitaHabitacion)
    {
        $visitaHabitacion = VisitaHabitacion::find($visita_habitacion);

        return $visita_habitacion;
    }

    public function update(VisitaHabitacionUpdateRequest $request, VisitaHabitacion $visitaHabitacion)
    {
        $visitaHabitacion = VisitaHabitacion::find($visita_habitacion);

        $visitaHabitacion->update($request->validated());

        return $visita_habitacion;
    }
}
