<?php

use App\Models\ReporteEnfermeria;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('castea enfermera_id como string uuid', function () {
    $reporteEnfermeria = ReporteEnfermeria::factory()->create();

    expect($reporteEnfermeria->enfermera_id)
        ->toBeString()
        ->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
});

it('castea ronda_enfermeria_id como integer', function () {
    $reporteEnfermeria = ReporteEnfermeria::factory()->create();

    expect($reporteEnfermeria->ronda_enfermeria_id)->toBeInt();
});
