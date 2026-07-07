<?php

use App\Models\ChecklistEnfermeriaItem;
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

it('permite a la enfermera dueña de la ronda ver y editar el item de checklist', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermera->id]);
    $visita = VisitaHabitacion::factory()->create(['ronda_enfermeria_id' => $ronda->id]);
    $item = ChecklistEnfermeriaItem::factory()->create(['visita_habitacion_id' => $visita->id]);

    Passport::actingAs($enfermera);

    expect(Gate::forUser($enfermera)->allows('view', $item))->toBeTrue()
        ->and(Gate::forUser($enfermera)->allows('update', $item))->toBeTrue()
        ->and(Gate::forUser($enfermera)->allows('viewAny', ChecklistEnfermeriaItem::class))->toBeTrue()
        ->and(Gate::forUser($enfermera)->allows('create', ChecklistEnfermeriaItem::class))->toBeTrue();
});

it('niega a una enfermera ver o editar el item de checklist de la ronda de otra enfermera', function () {
    $dueña = User::factory()->create();
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $dueña->id]);
    $visita = VisitaHabitacion::factory()->create(['ronda_enfermeria_id' => $ronda->id]);
    $item = ChecklistEnfermeriaItem::factory()->create(['visita_habitacion_id' => $visita->id]);

    $otra = User::factory()->create();
    $otra->assignRole('enfermera');

    Passport::actingAs($otra);

    expect(Gate::forUser($otra)->denies('view', $item))->toBeTrue()
        ->and(Gate::forUser($otra)->denies('update', $item))->toBeTrue();
});

it('permite a supervisor y admin ver y editar cualquier item de checklist sin importar el dueño', function () {
    $dueña = User::factory()->create();
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $dueña->id]);
    $visita = VisitaHabitacion::factory()->create(['ronda_enfermeria_id' => $ronda->id]);
    $item = ChecklistEnfermeriaItem::factory()->create(['visita_habitacion_id' => $visita->id]);

    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    Passport::actingAs($supervisor);
    expect(Gate::forUser($supervisor)->allows('view', $item))->toBeTrue()
        ->and(Gate::forUser($supervisor)->allows('update', $item))->toBeTrue();

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Passport::actingAs($admin);
    expect(Gate::forUser($admin)->allows('view', $item))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('update', $item))->toBeTrue();
});

it('niega a un usuario sin permiso ver o editar el item de checklist de su propia ronda', function () {
    $usuario = User::factory()->create();
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $usuario->id]);
    $visita = VisitaHabitacion::factory()->create(['ronda_enfermeria_id' => $ronda->id]);
    $item = ChecklistEnfermeriaItem::factory()->create(['visita_habitacion_id' => $visita->id]);

    Passport::actingAs($usuario);

    expect(Gate::forUser($usuario)->denies('view', $item))->toBeTrue()
        ->and(Gate::forUser($usuario)->denies('update', $item))->toBeTrue()
        ->and(Gate::forUser($usuario)->denies('viewAny', ChecklistEnfermeriaItem::class))->toBeTrue()
        ->and(Gate::forUser($usuario)->denies('create', ChecklistEnfermeriaItem::class))->toBeTrue();
});
