<?php

namespace App\Http\Controllers;

use App\Http\Requests\AlertaRondaUpdateRequest;
use App\Models\AlertaRonda;
use Illuminate\Http\Request;

class AlertaRondaController extends Controller
{
    public function index(Request $request)
    {
        $alertaRondas = AlertaRonda::all();

        return $alerta_rondas;
    }

    public function show(Request $request, AlertaRonda $alertaRonda)
    {
        $alertaRonda = AlertaRonda::find($alerta_ronda);

        return $alerta_ronda;
    }

    public function update(AlertaRondaUpdateRequest $request, AlertaRonda $alertaRonda)
    {
        $alertaRonda = AlertaRonda::find($alerta_ronda);

        $alertaRonda->update($request->validated());

        return $alerta_ronda;
    }
}
