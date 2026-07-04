<?php

use App\Models\Sucursal;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('usa uuid como clave primaria', function () {
    $user = User::factory()->create();

    expect($user->id)
        ->toBeString()
        ->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
});

it('is_super_admin es false por defecto', function () {
    $user = User::factory()->create();

    expect($user->is_super_admin)->toBeFalse();
});

it('activo es true por defecto', function () {
    $user = User::factory()->create();

    expect($user->activo)->toBeTrue();
});

it('sucursal_id es nullable', function () {
    $user = User::factory()->create(['sucursal_id' => null]);

    expect($user->sucursal_id)->toBeNull()
        ->and($user->sucursal)->toBeNull();
});

it('pertenece a una sucursal', function () {
    $sucursal = Sucursal::factory()->create();
    $user = User::factory()->create(['sucursal_id' => $sucursal->id]);

    expect($user->sucursal)->not->toBeNull()
        ->and($user->sucursal->id)->toBe($sucursal->id);
});

it('puede tener roles asignados', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('enfermera');

    expect($user->hasRole('enfermera'))->toBeTrue()
        ->and($user->hasRole('admin'))->toBeFalse();
});

it('puede tener multiples roles', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create();
    $user->assignRole(['admin', 'enfermera']);

    expect($user->hasRole('admin'))->toBeTrue()
        ->and($user->hasRole('enfermera'))->toBeTrue()
        ->and($user->roles)->toHaveCount(2);
});

it('super admin no necesita roles para existir', function () {
    $user = User::factory()->create(['is_super_admin' => true]);

    expect($user->roles)->toBeEmpty()
        ->and($user->is_super_admin)->toBeTrue();
});
