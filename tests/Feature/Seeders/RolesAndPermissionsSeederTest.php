<?php

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('crea exactamente 4 roles con guard api', function () {
    expect(Role::count())->toBe(4)
        ->and(Role::where('guard_name', 'api')->count())->toBe(4);
});

it('crea exactamente 43 permisos con guard api', function () {
    expect(Permission::count())->toBe(43)
        ->and(Permission::where('guard_name', 'api')->count())->toBe(43);
});

it('crea los roles con los nombres correctos', function () {
    expect(Role::pluck('name')->sort()->values()->all())
        ->toBe(['admin', 'camarera', 'enfermera', 'supervisor']);
});

it('el rol admin tiene 31 permisos', function () {
    expect(Role::findByName('admin', 'api')->permissions()->count())->toBe(31);
});

it('el rol supervisor tiene 16 permisos', function () {
    expect(Role::findByName('supervisor', 'api')->permissions()->count())->toBe(16);
});

it('el rol camarera tiene 6 permisos', function () {
    expect(Role::findByName('camarera', 'api')->permissions()->count())->toBe(6);
});

it('el rol enfermera tiene 16 permisos', function () {
    expect(Role::findByName('enfermera', 'api')->permissions()->count())->toBe(16);
});

it('el rol admin puede eliminar habitaciones pero no crear rondas de enfermeria', function () {
    $admin = Role::findByName('admin', 'api');
    $permisos = $admin->permissions->pluck('name');

    expect($permisos)->toContain('habitaciones.eliminar')
        ->and($permisos)->toContain('usuarios.crear')
        ->and($permisos)->not->toContain('rondas-enfermeria.crear');
});

it('el rol enfermera puede gestionar rondas pero no tareas de limpieza', function () {
    $enfermera = Role::findByName('enfermera', 'api');
    $permisos = $enfermera->permissions->pluck('name');

    expect($permisos)->toContain('rondas-enfermeria.ver')
        ->and($permisos)->toContain('rondas-enfermeria.crear')
        ->and($permisos)->toContain('rondas-enfermeria.editar')
        ->and($permisos)->not->toContain('tareas-limpieza.ver');
});

it('el rol camarera solo puede editar tareas no crearlas', function () {
    $camarera = Role::findByName('camarera', 'api');
    $permisos = $camarera->permissions->pluck('name');

    expect($permisos)->toContain('tareas-limpieza.editar')
        ->and($permisos)->not->toContain('tareas-limpieza.crear')
        ->and($permisos)->not->toContain('tareas-limpieza.eliminar');
});

it('ningun rol tiene permisos de sucursales create o edit', function () {
    $todosLosPermisos = Role::with('permissions')->get()
        ->flatMap(fn ($role) => $role->permissions->pluck('name'))
        ->unique();

    expect($todosLosPermisos)->not->toContain('sucursales.crear')
        ->and($todosLosPermisos)->not->toContain('sucursales.editar')
        ->and($todosLosPermisos)->not->toContain('sucursales.eliminar');
});
