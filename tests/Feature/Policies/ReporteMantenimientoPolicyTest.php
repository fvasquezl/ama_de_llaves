<?php

use App\Models\ReporteMantenimiento;
use App\Models\User;
use App\Policies\ReporteMantenimientoPolicy;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('permite al reportante dueño ver y editar su propio reporte', function () {
    $camarera = User::factory()->create();
    $camarera->assignRole('camarera');
    $camarera->givePermissionTo(['reportes-mantenimiento.ver', 'reportes-mantenimiento.editar']);
    $reporte = ReporteMantenimiento::factory()->create(['reportado_por_id' => $camarera->id]);

    Passport::actingAs($camarera);

    expect(Gate::forUser($camarera)->allows('view', $reporte))->toBeTrue()
        ->and(Gate::forUser($camarera)->allows('update', $reporte))->toBeTrue()
        ->and(Gate::forUser($camarera)->allows('viewAny', ReporteMantenimiento::class))->toBeTrue();
});

it('permite a la camarera crear reportes (fire-and-forget)', function () {
    $camarera = User::factory()->create();
    $camarera->assignRole('camarera');

    Passport::actingAs($camarera);

    expect(Gate::forUser($camarera)->allows('create', ReporteMantenimiento::class))->toBeTrue();
});

it('niega a una camarera ver o editar el reporte de otra camarera', function () {
    $dueña = User::factory()->create();
    $otra = User::factory()->create();
    $otra->assignRole('camarera');
    $otra->givePermissionTo(['reportes-mantenimiento.ver', 'reportes-mantenimiento.editar']);
    $reporte = ReporteMantenimiento::factory()->create(['reportado_por_id' => $dueña->id]);

    Passport::actingAs($otra);

    expect(Gate::forUser($otra)->denies('view', $reporte))->toBeTrue()
        ->and(Gate::forUser($otra)->denies('update', $reporte))->toBeTrue();
});

it('permite a supervisor y admin ver y editar cualquier reporte sin importar el dueño', function () {
    $dueña = User::factory()->create();
    $reporte = ReporteMantenimiento::factory()->create(['reportado_por_id' => $dueña->id]);

    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    Passport::actingAs($supervisor);
    expect(Gate::forUser($supervisor)->allows('view', $reporte))->toBeTrue()
        ->and(Gate::forUser($supervisor)->allows('update', $reporte))->toBeTrue();

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Passport::actingAs($admin);
    expect(Gate::forUser($admin)->allows('view', $reporte))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('update', $reporte))->toBeTrue();
});

it('niega a un usuario sin permiso ver, crear o editar reportes', function () {
    $usuario = User::factory()->create();
    $reporte = ReporteMantenimiento::factory()->create(['reportado_por_id' => $usuario->id]);

    Passport::actingAs($usuario);

    expect(Gate::forUser($usuario)->denies('view', $reporte))->toBeTrue()
        ->and(Gate::forUser($usuario)->denies('update', $reporte))->toBeTrue()
        ->and(Gate::forUser($usuario)->denies('viewAny', ReporteMantenimiento::class))->toBeTrue()
        ->and(Gate::forUser($usuario)->denies('create', ReporteMantenimiento::class))->toBeTrue();
});

it('no existe una acción delete para reportes-mantenimiento (el recurso no tiene ruta destroy)', function () {
    expect(method_exists(ReporteMantenimientoPolicy::class, 'delete'))->toBeFalse();
});
