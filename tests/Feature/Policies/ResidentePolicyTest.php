<?php

use App\Models\Residente;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('permite a admin ver, crear, editar y eliminar cualquier residente', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $residente = Residente::factory()->create();

    Passport::actingAs($admin);

    expect(Gate::forUser($admin)->allows('viewAny', Residente::class))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('view', $residente))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('create', Residente::class))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('update', $residente))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('delete', $residente))->toBeTrue();
});

it('permite a enfermera ver pero no crear, editar o eliminar un residente', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $residente = Residente::factory()->create();

    Passport::actingAs($enfermera);

    expect(Gate::forUser($enfermera)->allows('viewAny', Residente::class))->toBeTrue()
        ->and(Gate::forUser($enfermera)->allows('view', $residente))->toBeTrue()
        ->and(Gate::forUser($enfermera)->denies('create', Residente::class))->toBeTrue()
        ->and(Gate::forUser($enfermera)->denies('update', $residente))->toBeTrue()
        ->and(Gate::forUser($enfermera)->denies('delete', $residente))->toBeTrue();
});

it('niega a un usuario sin residentes.ver ver o listar residentes', function () {
    $usuario = User::factory()->create();
    $residente = Residente::factory()->create();

    Passport::actingAs($usuario);

    expect(Gate::forUser($usuario)->denies('viewAny', Residente::class))->toBeTrue()
        ->and(Gate::forUser($usuario)->denies('view', $residente))->toBeTrue();
});
