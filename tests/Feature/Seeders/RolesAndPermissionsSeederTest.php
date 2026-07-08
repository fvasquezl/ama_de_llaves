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

it('el rol admin tiene 43 permisos', function () {
    expect(Role::findByName('admin', 'api')->permissions()->count())->toBe(43);
});

it('el rol supervisor tiene 29 permisos', function () {
    expect(Role::findByName('supervisor', 'api')->permissions()->count())->toBe(29);
});

it('el rol camarera tiene 7 permisos', function () {
    expect(Role::findByName('camarera', 'api')->permissions()->count())->toBe(7);
});

it('el rol enfermera tiene 18 permisos', function () {
    expect(Role::findByName('enfermera', 'api')->permissions()->count())->toBe(18);
});

it('el rol admin puede eliminar habitaciones y crear rondas de enfermeria', function () {
    $admin = Role::findByName('admin', 'api');
    $permisos = $admin->permissions->pluck('name');

    expect($permisos)->toContain('habitaciones.eliminar')
        ->and($permisos)->toContain('usuarios.crear')
        ->and($permisos)->toContain('rondas-enfermeria.crear');
});

it('el rol admin tiene ver, crear y editar en los 5 grupos de permisos de enfermeria', function () {
    $admin = Role::findByName('admin', 'api');
    $permisos = $admin->permissions->pluck('name');

    foreach (['rondas-enfermeria', 'visitas-habitacion', 'checklist-enfermeria', 'reportes-enfermeria'] as $grupo) {
        expect($permisos)->toContain("{$grupo}.ver")
            ->and($permisos)->toContain("{$grupo}.crear")
            ->and($permisos)->toContain("{$grupo}.editar");
    }

    expect($permisos)->toContain('alertas-ronda.ver')
        ->and($permisos)->toContain('alertas-ronda.editar');
});

it('el rol supervisor tiene ver, crear y editar en los 5 grupos de permisos de enfermeria', function () {
    $supervisor = Role::findByName('supervisor', 'api');
    $permisos = $supervisor->permissions->pluck('name');

    foreach (['rondas-enfermeria', 'visitas-habitacion', 'checklist-enfermeria', 'reportes-enfermeria'] as $grupo) {
        expect($permisos)->toContain("{$grupo}.ver")
            ->and($permisos)->toContain("{$grupo}.crear")
            ->and($permisos)->toContain("{$grupo}.editar");
    }

    expect($permisos)->toContain('alertas-ronda.ver')
        ->and($permisos)->toContain('alertas-ronda.editar');
});

it('el rol enfermera tiene alertas-ronda.editar ademas de alertas-ronda.ver', function () {
    $enfermera = Role::findByName('enfermera', 'api');
    $permisos = $enfermera->permissions->pluck('name');

    expect($permisos)->toContain('alertas-ronda.ver')
        ->and($permisos)->toContain('alertas-ronda.editar');
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

it('solo el rol admin tiene permisos de sucursales crear, editar y eliminar', function () {
    $admin = Role::findByName('admin', 'api')->permissions->pluck('name');
    expect($admin)->toContain('sucursales.crear')
        ->and($admin)->toContain('sucursales.editar')
        ->and($admin)->toContain('sucursales.eliminar');

    foreach (['supervisor', 'camarera', 'enfermera'] as $roleName) {
        $permisos = Role::findByName($roleName, 'api')->permissions->pluck('name');

        expect($permisos)->not->toContain('sucursales.crear')
            ->and($permisos)->not->toContain('sucursales.editar')
            ->and($permisos)->not->toContain('sucursales.eliminar');
    }
});

it('ningun rol tiene los permisos eliminar retirados de limpieza y mantenimiento', function () {
    expect(Permission::where('name', 'tareas-limpieza.eliminar')->exists())->toBeFalse()
        ->and(Permission::where('name', 'checklist-limpieza.eliminar')->exists())->toBeFalse()
        ->and(Permission::where('name', 'reportes-mantenimiento.eliminar')->exists())->toBeFalse();

    foreach (['admin', 'supervisor', 'camarera', 'enfermera'] as $roleName) {
        $permisos = Role::findByName($roleName, 'api')->permissions->pluck('name');

        expect($permisos)->not->toContain('tareas-limpieza.eliminar')
            ->and($permisos)->not->toContain('checklist-limpieza.eliminar')
            ->and($permisos)->not->toContain('reportes-mantenimiento.eliminar');
    }
});

it('el rol admin tiene paridad con supervisor en checklist-limpieza e inspecciones', function () {
    $admin = Role::findByName('admin', 'api')->permissions->pluck('name');

    expect($admin)->toContain('checklist-limpieza.crear')
        ->and($admin)->toContain('checklist-limpieza.editar')
        ->and($admin)->toContain('inspecciones.crear');
});

it('el rol camarera puede ver los reportes de mantenimiento que crea', function () {
    $camarera = Role::findByName('camarera', 'api')->permissions->pluck('name');

    expect($camarera)->toContain('reportes-mantenimiento.ver')
        ->and($camarera)->toContain('reportes-mantenimiento.crear');
});

it('el rol enfermera puede crear estancias', function () {
    $enfermera = Role::findByName('enfermera', 'api')->permissions->pluck('name');

    expect($enfermera)->toContain('estancias.crear');
});
