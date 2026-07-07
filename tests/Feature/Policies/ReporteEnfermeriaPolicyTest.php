<?php

use App\Models\ReporteEnfermeria;
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

it('permite a la enfermera dueña ver y editar su propio reporte', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermera->id]);
    $reporte = ReporteEnfermeria::factory()->create([
        'ronda_enfermeria_id' => $ronda->id,
        'enfermera_id' => $enfermera->id,
    ]);

    Passport::actingAs($enfermera);

    expect(Gate::forUser($enfermera)->allows('view', $reporte))->toBeTrue()
        ->and(Gate::forUser($enfermera)->allows('update', $reporte))->toBeTrue()
        ->and(Gate::forUser($enfermera)->allows('viewAny', ReporteEnfermeria::class))->toBeTrue()
        ->and(Gate::forUser($enfermera)->allows('create', ReporteEnfermeria::class))->toBeTrue();
});

it('niega a una enfermera ver o editar el reporte de otra enfermera', function () {
    $autora = User::factory()->create();
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $autora->id]);
    $reporte = ReporteEnfermeria::factory()->create([
        'ronda_enfermeria_id' => $ronda->id,
        'enfermera_id' => $autora->id,
    ]);

    $otra = User::factory()->create();
    $otra->assignRole('enfermera');

    Passport::actingAs($otra);

    expect(Gate::forUser($otra)->denies('view', $reporte))->toBeTrue()
        ->and(Gate::forUser($otra)->denies('update', $reporte))->toBeTrue();
});

it('permite a supervisor y admin ver y editar cualquier reporte sin importar el autor', function () {
    $autora = User::factory()->create();
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $autora->id]);
    $reporte = ReporteEnfermeria::factory()->create([
        'ronda_enfermeria_id' => $ronda->id,
        'enfermera_id' => $autora->id,
    ]);

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

it('niega a un usuario sin permiso ver o editar su propio reporte', function () {
    $usuario = User::factory()->create();
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $usuario->id]);
    $reporte = ReporteEnfermeria::factory()->create([
        'ronda_enfermeria_id' => $ronda->id,
        'enfermera_id' => $usuario->id,
    ]);

    Passport::actingAs($usuario);

    expect(Gate::forUser($usuario)->denies('view', $reporte))->toBeTrue()
        ->and(Gate::forUser($usuario)->denies('update', $reporte))->toBeTrue()
        ->and(Gate::forUser($usuario)->denies('viewAny', ReporteEnfermeria::class))->toBeTrue()
        ->and(Gate::forUser($usuario)->denies('create', ReporteEnfermeria::class))->toBeTrue();
});

it('la propiedad del reporte se basa en enfermera_id propio, no en el dueño de la ronda', function () {
    $dueñaDeRonda = User::factory()->create();
    $autoraDelReporte = User::factory()->create();
    $autoraDelReporte->assignRole('enfermera');
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $dueñaDeRonda->id]);
    $reporte = ReporteEnfermeria::factory()->create([
        'ronda_enfermeria_id' => $ronda->id,
        'enfermera_id' => $autoraDelReporte->id,
    ]);

    Passport::actingAs($autoraDelReporte);

    expect(Gate::forUser($autoraDelReporte)->allows('view', $reporte))->toBeTrue()
        ->and(Gate::forUser($autoraDelReporte)->allows('update', $reporte))->toBeTrue();
});
