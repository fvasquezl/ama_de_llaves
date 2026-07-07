<?php

use App\Models\RondaEnfermeria;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('permite a la enfermera dueña ver y editar su propia ronda', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermera->id]);

    Passport::actingAs($enfermera);

    expect(Gate::forUser($enfermera)->allows('view', $ronda))->toBeTrue()
        ->and(Gate::forUser($enfermera)->allows('update', $ronda))->toBeTrue()
        ->and(Gate::forUser($enfermera)->allows('viewAny', RondaEnfermeria::class))->toBeTrue()
        ->and(Gate::forUser($enfermera)->allows('create', RondaEnfermeria::class))->toBeTrue();
});

it('niega a una enfermera ver o editar la ronda de otra enfermera', function () {
    $dueña = User::factory()->create();
    $otra = User::factory()->create();
    $otra->assignRole('enfermera');
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $dueña->id]);

    Passport::actingAs($otra);

    expect(Gate::forUser($otra)->denies('view', $ronda))->toBeTrue()
        ->and(Gate::forUser($otra)->denies('update', $ronda))->toBeTrue();
});

it('permite a supervisor y admin ver y editar cualquier ronda sin importar el dueño', function () {
    $dueña = User::factory()->create();
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $dueña->id]);

    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    Passport::actingAs($supervisor);
    expect(Gate::forUser($supervisor)->allows('view', $ronda))->toBeTrue()
        ->and(Gate::forUser($supervisor)->allows('update', $ronda))->toBeTrue();

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Passport::actingAs($admin);
    expect(Gate::forUser($admin)->allows('view', $ronda))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('update', $ronda))->toBeTrue();
});

it('niega a un usuario sin permiso ver o editar su propia ronda', function () {
    $usuario = User::factory()->create();
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $usuario->id]);

    Passport::actingAs($usuario);

    expect(Gate::forUser($usuario)->denies('view', $ronda))->toBeTrue()
        ->and(Gate::forUser($usuario)->denies('update', $ronda))->toBeTrue()
        ->and(Gate::forUser($usuario)->denies('viewAny', RondaEnfermeria::class))->toBeTrue()
        ->and(Gate::forUser($usuario)->denies('create', RondaEnfermeria::class))->toBeTrue();
});
