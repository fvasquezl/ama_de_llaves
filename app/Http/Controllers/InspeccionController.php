<?php

namespace App\Http\Controllers;

use App\Http\Requests\InspeccionStoreRequest;
use App\Models\Inspeccion;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InspeccionController extends Controller
{
    public function index(Request $request)
    {
        $inspeccions = Inspeccion::all();

        return $inspecciones;
    }

    public function store(InspeccionStoreRequest $request): Response
    {
        $inspeccion = Inspeccion::create($request->validated());

        return response()->noContent(201);
    }

    public function show(Request $request, Inspeccion $inspeccion)
    {
        $inspeccion = Inspeccion::find($inspeccion);

        return $inspeccion;
    }
}
