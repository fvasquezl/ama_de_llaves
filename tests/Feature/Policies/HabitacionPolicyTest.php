<?php

use App\Models\Habitacion;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('permite a admin ver, crear, editar y eliminar cualquier habitacion', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $habitacion = Habitacion::factory()->create();

    Passport::actingAs($admin);

    expect(Gate::forUser($admin)->allows('viewAny', Habitacion::class))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('view', $habitacion))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('create', Habitacion::class))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('update', $habitacion))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('delete', $habitacion))->toBeTrue();
});

it('permite a camarera ver pero no crear, editar o eliminar una habitacion', function () {
    $camarera = User::factory()->create();
    $camarera->assignRole('camarera');
    $habitacion = Habitacion::factory()->create();

    Passport::actingAs($camarera);

    expect(Gate::forUser($camarera)->allows('viewAny', Habitacion::class))->toBeTrue()
        ->and(Gate::forUser($camarera)->allows('view', $habitacion))->toBeTrue()
        ->and(Gate::forUser($camarera)->denies('create', Habitacion::class))->toBeTrue()
        ->and(Gate::forUser($camarera)->denies('update', $habitacion))->toBeTrue()
        ->and(Gate::forUser($camarera)->denies('delete', $habitacion))->toBeTrue();
});

it('niega a un usuario sin habitaciones.ver ver o listar habitaciones', function () {
    $usuario = User::factory()->create();
    $habitacion = Habitacion::factory()->create();

    Passport::actingAs($usuario);

    expect(Gate::forUser($usuario)->denies('viewAny', Habitacion::class))->toBeTrue()
        ->and(Gate::forUser($usuario)->denies('view', $habitacion))->toBeTrue();
});
