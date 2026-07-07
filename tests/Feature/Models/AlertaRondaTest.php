<?php

use App\Models\AlertaRonda;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('castea atendido_por_id como string uuid', function () {
    $alertaRonda = AlertaRonda::factory()->create();

    expect($alertaRonda->atendido_por_id)
        ->toBeString()
        ->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
});

it('castea ronda_enfermeria_id y visita_habitacion_id como integer', function () {
    $alertaRonda = AlertaRonda::factory()->create();

    expect($alertaRonda->ronda_enfermeria_id)->toBeInt()
        ->and($alertaRonda->visita_habitacion_id)->toBeInt();
});
