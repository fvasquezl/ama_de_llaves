<?php

namespace App\Http\Controllers;

use App\Http\Requests\EstanciumStoreRequest;
use App\Http\Requests\EstanciumUpdateRequest;
use App\Models\Estancia;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EstanciaController extends Controller
{
    public function index(Request $request)
    {
        $estancia = Estancium::all();

        return $estancias;
    }

    public function store(EstanciumStoreRequest $request): Response
    {
        $estancia = Estancia::create($request->validated());

        return response()->noContent(201);
    }

    public function show(Request $request, Estancium $estancium)
    {
        $estancia = Estancia::find($estancia);

        return $estancia;
    }

    public function update(EstanciumUpdateRequest $request, Estancium $estancium)
    {
        $estancia = Estancia::find($estancia);

        $estancia->update($request->validated());

        return $estancia;
    }
}
