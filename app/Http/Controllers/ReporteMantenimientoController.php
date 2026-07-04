<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReporteMantenimientoStoreRequest;
use App\Http\Requests\ReporteMantenimientoUpdateRequest;
use App\Models\ReporteMantenimiento;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReporteMantenimientoController extends Controller
{
    public function index(Request $request)
    {
        $reporteMantenimientos = ReporteMantenimiento::all();

        return $reporte_mantenimientos;
    }

    public function store(ReporteMantenimientoStoreRequest $request): Response
    {
        $reporteMantenimiento = ReporteMantenimiento::create($request->validated());

        return response()->noContent(201);
    }

    public function show(Request $request, ReporteMantenimiento $reporteMantenimiento)
    {
        $reporteMantenimiento = ReporteMantenimiento::find($reporte_mantenimiento);

        return $reporte_mantenimiento;
    }

    public function update(ReporteMantenimientoUpdateRequest $request, ReporteMantenimiento $reporteMantenimiento)
    {
        $reporteMantenimiento = ReporteMantenimiento::find($reporte_mantenimiento);

        $reporteMantenimiento->update($request->validated());

        return $reporte_mantenimiento;
    }

    public function destroy(Request $request, ReporteMantenimiento $reporteMantenimiento): Response
    {
        $reporteMantenimiento = ReporteMantenimiento::find($reporte_mantenimiento);

        $reporteMantenimiento->delete();

        return response()->noContent();
    }
}
