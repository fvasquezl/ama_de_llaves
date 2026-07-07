<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('super admin bypasea todos los permisos del gate sin tener ninguno asignado', function () {
    $user = User::factory()->create(['is_super_admin' => true]);

    Passport::actingAs($user);

    expect($user->roles)->toBeEmpty()
        ->and($user->permissions)->toBeEmpty()
        ->and(Gate::forUser($user)->allows('habitaciones.ver'))->toBeTrue()
        ->and(Gate::forUser($user)->allows('residentes.eliminar'))->toBeTrue()
        ->and(Gate::forUser($user)->allows('cualquier.permiso.inventado'))->toBeTrue();
});

it('un usuario sin rol no puede realizar ninguna accion', function () {
    $user = User::factory()->create(['is_super_admin' => false]);

    Passport::actingAs($user);

    expect(Gate::forUser($user)->denies('habitaciones.ver'))->toBeTrue()
        ->and(Gate::forUser($user)->denies('residentes.crear'))->toBeTrue();
});

it('enfermera solo puede realizar las acciones asignadas a su rol', function () {
    $user = User::factory()->create(['is_super_admin' => false]);
    $user->assignRole('enfermera');

    Passport::actingAs($user);

    expect(Gate::forUser($user)->allows('rondas-enfermeria.ver'))->toBeTrue()
        ->and(Gate::forUser($user)->allows('rondas-enfermeria.crear'))->toBeTrue()
        ->and(Gate::forUser($user)->allows('rondas-enfermeria.editar'))->toBeTrue()
        ->and(Gate::forUser($user)->denies('tareas-limpieza.ver'))->toBeTrue()
        ->and(Gate::forUser($user)->denies('usuarios.crear'))->toBeTrue();
});

it('camarera solo puede realizar las acciones asignadas a su rol', function () {
    $user = User::factory()->create(['is_super_admin' => false]);
    $user->assignRole('camarera');

    Passport::actingAs($user);

    expect(Gate::forUser($user)->allows('tareas-limpieza.ver'))->toBeTrue()
        ->and(Gate::forUser($user)->allows('tareas-limpieza.editar'))->toBeTrue()
        ->and(Gate::forUser($user)->allows('reportes-mantenimiento.crear'))->toBeTrue()
        ->and(Gate::forUser($user)->denies('tareas-limpieza.crear'))->toBeTrue()
        ->and(Gate::forUser($user)->denies('residentes.ver'))->toBeTrue();
});

it('supervisor puede gestionar limpieza y enfermeria pero no crear residentes', function () {
    $user = User::factory()->create(['is_super_admin' => false]);
    $user->assignRole('supervisor');

    Passport::actingAs($user);

    expect(Gate::forUser($user)->allows('tareas-limpieza.ver'))->toBeTrue()
        ->and(Gate::forUser($user)->allows('inspecciones.crear'))->toBeTrue()
        ->and(Gate::forUser($user)->allows('rondas-enfermeria.ver'))->toBeTrue()
        ->and(Gate::forUser($user)->allows('rondas-enfermeria.crear'))->toBeTrue()
        ->and(Gate::forUser($user)->denies('residentes.crear'))->toBeTrue();
});

it('admin puede gestionar usuarios, habitaciones y crear rondas', function () {
    $user = User::factory()->create(['is_super_admin' => false]);
    $user->assignRole('admin');

    Passport::actingAs($user);

    expect(Gate::forUser($user)->allows('usuarios.crear'))->toBeTrue()
        ->and(Gate::forUser($user)->allows('habitaciones.eliminar'))->toBeTrue()
        ->and(Gate::forUser($user)->allows('rondas-enfermeria.crear'))->toBeTrue();
});
