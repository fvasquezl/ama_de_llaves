<?php

use App\Models\Sucursal;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('permite a admin ver, crear, editar y eliminar cualquier sucursal', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $sucursal = Sucursal::factory()->create();

    Passport::actingAs($admin);

    expect(Gate::forUser($admin)->allows('viewAny', Sucursal::class))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('view', $sucursal))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('create', Sucursal::class))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('update', $sucursal))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('delete', $sucursal))->toBeTrue();
});

it('niega a supervisor crear, editar o eliminar una sucursal', function () {
    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    $sucursal = Sucursal::factory()->create();

    Passport::actingAs($supervisor);

    expect(Gate::forUser($supervisor)->denies('create', Sucursal::class))->toBeTrue()
        ->and(Gate::forUser($supervisor)->denies('update', $sucursal))->toBeTrue()
        ->and(Gate::forUser($supervisor)->denies('delete', $sucursal))->toBeTrue();
});

it('niega a un usuario sin sucursales.ver ver o listar sucursales', function () {
    $usuario = User::factory()->create();
    $sucursal = Sucursal::factory()->create();

    Passport::actingAs($usuario);

    expect(Gate::forUser($usuario)->denies('viewAny', Sucursal::class))->toBeTrue()
        ->and(Gate::forUser($usuario)->denies('view', $sucursal))->toBeTrue();
});
