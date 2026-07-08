<?php

use App\Models\ReporteMantenimiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('castea reportado_por_id como string uuid', function () {
    $reporteMantenimiento = ReporteMantenimiento::factory()->create();

    expect($reporteMantenimiento->reportado_por_id)
        ->toBeString()
        ->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
});

it('preserva el UUID exacto del usuario creador en reportado_por_id tras la hidratación de Eloquent (design part-29/31)', function () {
    $reportadoPor = User::factory()->create();
    $reporteMantenimiento = ReporteMantenimiento::factory()->create(['reportado_por_id' => $reportadoPor->id]);

    expect($reporteMantenimiento->fresh()->reportado_por_id)->toBe($reportadoPor->id);
});

it('castea habitacion_id como integer', function () {
    $reporteMantenimiento = ReporteMantenimiento::factory()->create();

    expect($reporteMantenimiento->habitacion_id)->toBeInt();
});
