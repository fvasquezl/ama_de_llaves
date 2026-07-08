<?php

use App\Models\TareaLimpieza;
use App\Models\User;
use App\Policies\TareaLimpiezaPolicy;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('permite a la camarera dueña ver y editar su propia tarea', function () {
    $camarera = User::factory()->create();
    $camarera->assignRole('camarera');
    $tarea = TareaLimpieza::factory()->create(['camarera_id' => $camarera->id]);

    Passport::actingAs($camarera);

    expect(Gate::forUser($camarera)->allows('view', $tarea))->toBeTrue()
        ->and(Gate::forUser($camarera)->allows('update', $tarea))->toBeTrue()
        ->and(Gate::forUser($camarera)->allows('viewAny', TareaLimpieza::class))->toBeTrue();
});

it('niega a la camarera crear tareas (tiene ver/editar, no crear)', function () {
    $camarera = User::factory()->create();
    $camarera->assignRole('camarera');

    Passport::actingAs($camarera);

    expect(Gate::forUser($camarera)->denies('create', TareaLimpieza::class))->toBeTrue();
});

it('niega a una camarera ver o editar la tarea de otra camarera', function () {
    $dueña = User::factory()->create();
    $otra = User::factory()->create();
    $otra->assignRole('camarera');
    $tarea = TareaLimpieza::factory()->create(['camarera_id' => $dueña->id]);

    Passport::actingAs($otra);

    expect(Gate::forUser($otra)->denies('view', $tarea))->toBeTrue()
        ->and(Gate::forUser($otra)->denies('update', $tarea))->toBeTrue();
});

it('niega a cualquier camarera ver o editar una tarea sin asignar (camarera_id null)', function () {
    $tarea = TareaLimpieza::factory()->create(['camarera_id' => null]);

    $camarera = User::factory()->create();
    $camarera->assignRole('camarera');
    Passport::actingAs($camarera);

    expect(Gate::forUser($camarera)->denies('view', $tarea))->toBeTrue()
        ->and(Gate::forUser($camarera)->denies('update', $tarea))->toBeTrue();
});

it('permite a supervisor y admin ver y editar cualquier tarea sin importar el dueño, incluidas las no asignadas', function () {
    $dueña = User::factory()->create();
    $tarea = TareaLimpieza::factory()->create(['camarera_id' => $dueña->id]);
    $sinAsignar = TareaLimpieza::factory()->create(['camarera_id' => null]);

    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    Passport::actingAs($supervisor);
    expect(Gate::forUser($supervisor)->allows('view', $tarea))->toBeTrue()
        ->and(Gate::forUser($supervisor)->allows('update', $tarea))->toBeTrue()
        ->and(Gate::forUser($supervisor)->allows('view', $sinAsignar))->toBeTrue()
        ->and(Gate::forUser($supervisor)->allows('update', $sinAsignar))->toBeTrue();

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Passport::actingAs($admin);
    expect(Gate::forUser($admin)->allows('view', $tarea))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('update', $tarea))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('view', $sinAsignar))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('update', $sinAsignar))->toBeTrue();
});

it('niega a un usuario sin permiso ver, crear o editar tareas', function () {
    $usuario = User::factory()->create();
    $tarea = TareaLimpieza::factory()->create(['camarera_id' => $usuario->id]);

    Passport::actingAs($usuario);

    expect(Gate::forUser($usuario)->denies('view', $tarea))->toBeTrue()
        ->and(Gate::forUser($usuario)->denies('update', $tarea))->toBeTrue()
        ->and(Gate::forUser($usuario)->denies('viewAny', TareaLimpieza::class))->toBeTrue()
        ->and(Gate::forUser($usuario)->denies('create', TareaLimpieza::class))->toBeTrue();
});

it('no existe una acción delete para tareas-limpieza (el recurso no tiene ruta destroy)', function () {
    expect(method_exists(TareaLimpiezaPolicy::class, 'delete'))->toBeFalse();
});
