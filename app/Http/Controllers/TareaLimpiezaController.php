<?php

namespace App\Http\Controllers;

use App\Http\Requests\TareaLimpiezaStoreRequest;
use App\Http\Requests\TareaLimpiezaUpdateRequest;
use App\Models\TareaLimpieza;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TareaLimpiezaController extends Controller
{
    public function index(Request $request)
    {
        $tareaLimpiezas = TareaLimpieza::all();

        return $tarea_limpiezas;
    }

    public function store(TareaLimpiezaStoreRequest $request): Response
    {
        $tareaLimpieza = TareaLimpieza::create($request->validated());

        return response()->noContent(201);
    }

    public function show(Request $request, TareaLimpieza $tareaLimpieza)
    {
        $tareaLimpieza = TareaLimpieza::find($tarea_limpieza);

        return $tarea_limpieza;
    }

    public function update(TareaLimpiezaUpdateRequest $request, TareaLimpieza $tareaLimpieza)
    {
        $tareaLimpieza = TareaLimpieza::find($tarea_limpieza);

        $tareaLimpieza->update($request->validated());

        return $tarea_limpieza;
    }

    public function destroy(Request $request, TareaLimpieza $tareaLimpieza): Response
    {
        $tareaLimpieza = TareaLimpieza::find($tarea_limpieza);

        $tareaLimpieza->delete();

        return response()->noContent();
    }
}
