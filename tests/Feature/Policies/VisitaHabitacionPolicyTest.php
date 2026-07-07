<?php

use App\Models\RondaEnfermeria;
use App\Models\User;
use App\Models\VisitaHabitacion;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('permite a la enfermera dueña de la ronda ver y editar la visita', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermera->id]);
    $visita = VisitaHabitacion::factory()->create(['ronda_enfermeria_id' => $ronda->id]);

    Passport::actingAs($enfermera);

    expect(Gate::forUser($enfermera)->allows('view', $visita))->toBeTrue()
        ->and(Gate::forUser($enfermera)->allows('update', $visita))->toBeTrue()
        ->and(Gate::forUser($enfermera)->allows('viewAny', VisitaHabitacion::class))->toBeTrue()
        ->and(Gate::forUser($enfermera)->allows('create', VisitaHabitacion::class))->toBeTrue();
});

it('niega a una enfermera ver o editar la visita de la ronda de otra enfermera', function () {
    $dueña = User::factory()->create();
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $dueña->id]);
    $visita = VisitaHabitacion::factory()->create(['ronda_enfermeria_id' => $ronda->id]);

    $otra = User::factory()->create();
    $otra->assignRole('enfermera');

    Passport::actingAs($otra);

    expect(Gate::forUser($otra)->denies('view', $visita))->toBeTrue()
        ->and(Gate::forUser($otra)->denies('update', $visita))->toBeTrue();
});

it('permite a supervisor y admin ver y editar cualquier visita sin importar el dueño', function () {
    $dueña = User::factory()->create();
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $dueña->id]);
    $visita = VisitaHabitacion::factory()->create(['ronda_enfermeria_id' => $ronda->id]);

    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    Passport::actingAs($supervisor);
    expect(Gate::forUser($supervisor)->allows('view', $visita))->toBeTrue()
        ->and(Gate::forUser($supervisor)->allows('update', $visita))->toBeTrue();

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Passport::actingAs($admin);
    expect(Gate::forUser($admin)->allows('view', $visita))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('update', $visita))->toBeTrue();
});

it('niega a un usuario sin permiso ver o editar la visita de su propia ronda', function () {
    $usuario = User::factory()->create();
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $usuario->id]);
    $visita = VisitaHabitacion::factory()->create(['ronda_enfermeria_id' => $ronda->id]);

    Passport::actingAs($usuario);

    expect(Gate::forUser($usuario)->denies('view', $visita))->toBeTrue()
        ->and(Gate::forUser($usuario)->denies('update', $visita))->toBeTrue()
        ->and(Gate::forUser($usuario)->denies('viewAny', VisitaHabitacion::class))->toBeTrue()
        ->and(Gate::forUser($usuario)->denies('create', VisitaHabitacion::class))->toBeTrue();
});
