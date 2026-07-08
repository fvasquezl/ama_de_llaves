<?php

use App\Models\Inspeccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('castea supervisora_id como string uuid', function () {
    $inspeccion = Inspeccion::factory()->create();

    expect($inspeccion->supervisora_id)
        ->toBeString()
        ->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
});

it('preserva el UUID exacto del usuario creador en supervisora_id tras la hidratación de Eloquent (design part-29/31)', function () {
    $supervisora = User::factory()->create();
    $inspeccion = Inspeccion::factory()->create(['supervisora_id' => $supervisora->id]);

    expect($inspeccion->fresh()->supervisora_id)->toBe($supervisora->id);
});

it('castea tarea_limpieza_id como integer', function () {
    $inspeccion = Inspeccion::factory()->create();

    expect($inspeccion->tarea_limpieza_id)->toBeInt();
});
