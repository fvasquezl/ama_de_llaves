<?php

namespace App\Http\Controllers;

use App\Http\Requests\SucursalStoreRequest;
use App\Http\Requests\SucursalUpdateRequest;
use App\Models\Sucursal;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SucursalController extends Controller
{
    public function index(Request $request)
    {
        $sucursals = Sucursal::all();

        return $sucursales;
    }

    public function store(SucursalStoreRequest $request): Response
    {
        $sucursal = Sucursal::create($request->validated());

        return response()->noContent(201);
    }

    public function show(Request $request, Sucursal $sucursal)
    {
        $sucursal = Sucursal::find($sucursal);

        return $sucursal;
    }

    public function update(SucursalUpdateRequest $request, Sucursal $sucursal)
    {
        $sucursal = Sucursal::find($sucursal);

        $sucursal->update($request->validated());

        return $sucursal;
    }

    public function destroy(Request $request, Sucursal $sucursal): Response
    {
        $sucursal = Sucursal::find($sucursal);

        $sucursal->delete();

        return response()->noContent();
    }
}
