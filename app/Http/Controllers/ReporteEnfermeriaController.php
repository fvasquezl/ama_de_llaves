<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReporteEnfermeriumStoreRequest;
use App\Http\Requests\ReporteEnfermeriumUpdateRequest;
use App\Models\ReporteEnfermeria;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReporteEnfermeriaController extends Controller
{
    public function index(Request $request)
    {
        $reporteEnfermeria = ReporteEnfermerium::all();

        return $reporte_enformerias;
    }

    public function store(ReporteEnfermeriumStoreRequest $request): Response
    {
        $reporteEnfermeria = ReporteEnfermeria::create($request->validated());

        return response()->noContent(201);
    }

    public function show(Request $request, ReporteEnfermerium $reporteEnfermerium)
    {
        $reporteEnfermeria = ReporteEnfermeria::find($reporte_enfermeria);

        return $reporte_enfermeria;
    }

    public function update(ReporteEnfermeriumUpdateRequest $request, ReporteEnfermerium $reporteEnfermerium)
    {
        $reporteEnfermeria = ReporteEnfermeria::find($reporte_enfermeria);

        $reporteEnfermeria->update($request->validated());

        return $reporte_enfermeria;
    }
}
