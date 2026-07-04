<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResidenteStoreRequest;
use App\Http\Requests\ResidenteUpdateRequest;
use App\Models\Residente;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ResidenteController extends Controller
{
    public function index(Request $request)
    {
        $residentes = Residente::all();

        return $residentes;
    }

    public function store(ResidenteStoreRequest $request): Response
    {
        $residente = Residente::create($request->validated());

        return response()->noContent(201);
    }

    public function show(Request $request, Residente $residente)
    {
        $residente = Residente::find($residente);

        return $residente;
    }

    public function update(ResidenteUpdateRequest $request, Residente $residente)
    {
        $residente = Residente::find($residente);

        $residente->update($request->validated());

        return $residente;
    }

    public function destroy(Request $request, Residente $residente): Response
    {
        $residente = Residente::find($residente);

        $residente->delete();

        return response()->noContent();
    }
}
