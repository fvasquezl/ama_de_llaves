<?php

use App\Models\RondaEnfermeria;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('castea enfermera_id como string uuid', function () {
    $rondaEnfermeria = RondaEnfermeria::factory()->create();

    expect($rondaEnfermeria->enfermera_id)
        ->toBeString()
        ->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
});

it('no define una relacion user fantasma', function () {
    expect(method_exists(RondaEnfermeria::class, 'user'))->toBeFalse();
});

it('no tiene un cast user_id fantasma', function () {
    $rondaEnfermeria = RondaEnfermeria::factory()->make();

    expect($rondaEnfermeria->getCasts())->not->toHaveKey('user_id');
});
