<?php

namespace App\Http\Controllers;

use App\Http\Requests\HabitacionStoreRequest;
use App\Http\Requests\HabitacionUpdateRequest;
use App\Models\Habitacion;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class HabitacionController extends Controller
{
    public function index(Request $request)
    {
        $habitacions = Habitacion::all();

        return $habitaciones;
    }

    public function store(HabitacionStoreRequest $request): Response
    {
        $habitacion = Habitacion::create($request->validated());

        return response()->noContent(201);
    }

    public function show(Request $request, Habitacion $habitacion)
    {
        $habitacion = Habitacion::find($habitacion);

        return $habitacion;
    }

    public function update(HabitacionUpdateRequest $request, Habitacion $habitacion)
    {
        $habitacion = Habitacion::find($habitacion);

        $habitacion->update($request->validated());

        return $habitacion;
    }

    public function destroy(Request $request, Habitacion $habitacion): Response
    {
        $habitacion = Habitacion::find($habitacion);

        $habitacion->delete();

        return response()->noContent();
    }
}
