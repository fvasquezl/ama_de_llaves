<?php

namespace App\Http\Controllers;

use App\Http\Requests\RondaEnfermeriumStoreRequest;
use App\Http\Requests\RondaEnfermeriumUpdateRequest;
use App\Models\RondaEnfermeria;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RondaEnfermeriaController extends Controller
{
    public function index(Request $request)
    {
        $rondaEnfermeria = RondaEnfermerium::all();

        return $ronda_enformerias;
    }

    public function store(RondaEnfermeriumStoreRequest $request): Response
    {
        $rondaEnfermeria = RondaEnfermeria::create($request->validated());

        return response()->noContent(201);
    }

    public function show(Request $request, RondaEnfermerium $rondaEnfermerium)
    {
        $rondaEnfermeria = RondaEnfermeria::find($ronda_enfermeria);

        return $ronda_enfermeria;
    }

    public function update(RondaEnfermeriumUpdateRequest $request, RondaEnfermerium $rondaEnfermerium)
    {
        $rondaEnfermeria = RondaEnfermeria::find($ronda_enfermeria);

        $rondaEnfermeria->update($request->validated());

        return $ronda_enfermeria;
    }

    public function destroy(Request $request, RondaEnfermerium $rondaEnfermerium): Response
    {
        $rondaEnfermeria = RondaEnfermeria::find($ronda_enfermeria);

        $rondaEnfermeria->delete();

        return response()->noContent();
    }
}
