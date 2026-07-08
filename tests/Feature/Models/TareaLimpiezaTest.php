<?php

use App\Models\TareaLimpieza;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('castea camarera_id y supervisora_id como string uuid', function () {
    $tareaLimpieza = TareaLimpieza::factory()->create();

    expect($tareaLimpieza->camarera_id)
        ->toBeString()
        ->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i')
        ->and($tareaLimpieza->supervisora_id)
        ->toBeString()
        ->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
});

it('preserva el UUID exacto del usuario creador en camarera_id y supervisora_id tras la hidratación de Eloquent (design part-29/31)', function () {
    $camarera = User::factory()->create();
    $supervisora = User::factory()->create();
    $tareaLimpieza = TareaLimpieza::factory()->create([
        'camarera_id' => $camarera->id,
        'supervisora_id' => $supervisora->id,
    ]);

    $fresh = $tareaLimpieza->fresh();

    expect($fresh->camarera_id)->toBe($camarera->id)
        ->and($fresh->supervisora_id)->toBe($supervisora->id);
});

it('castea habitacion_id como integer', function () {
    $tareaLimpieza = TareaLimpieza::factory()->create();

    expect($tareaLimpieza->habitacion_id)->toBeInt();
});
