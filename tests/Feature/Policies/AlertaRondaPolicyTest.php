<?php

use App\Models\AlertaRonda;
use App\Models\RondaEnfermeria;
use App\Models\User;
use App\Policies\AlertaRondaPolicy;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('permite a la enfermera dueña de la ronda ver y editar la alerta', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermera->id]);
    $alerta = AlertaRonda::factory()->create(['ronda_enfermeria_id' => $ronda->id]);

    Passport::actingAs($enfermera);

    expect(Gate::forUser($enfermera)->allows('view', $alerta))->toBeTrue()
        ->and(Gate::forUser($enfermera)->allows('update', $alerta))->toBeTrue()
        ->and(Gate::forUser($enfermera)->allows('viewAny', AlertaRonda::class))->toBeTrue();
});

it('niega a una enfermera ver o editar la alerta de la ronda de otra enfermera', function () {
    $dueña = User::factory()->create();
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $dueña->id]);
    $alerta = AlertaRonda::factory()->create(['ronda_enfermeria_id' => $ronda->id]);

    $otra = User::factory()->create();
    $otra->assignRole('enfermera');

    Passport::actingAs($otra);

    expect(Gate::forUser($otra)->denies('view', $alerta))->toBeTrue()
        ->and(Gate::forUser($otra)->denies('update', $alerta))->toBeTrue();
});

it('permite a supervisor y admin ver y editar cualquier alerta sin importar el dueño', function () {
    $dueña = User::factory()->create();
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $dueña->id]);
    $alerta = AlertaRonda::factory()->create(['ronda_enfermeria_id' => $ronda->id]);

    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    Passport::actingAs($supervisor);
    expect(Gate::forUser($supervisor)->allows('view', $alerta))->toBeTrue()
        ->and(Gate::forUser($supervisor)->allows('update', $alerta))->toBeTrue();

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Passport::actingAs($admin);
    expect(Gate::forUser($admin)->allows('view', $alerta))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('update', $alerta))->toBeTrue();
});

it('niega a un usuario sin permiso ver o editar la alerta de su propia ronda', function () {
    $usuario = User::factory()->create();
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $usuario->id]);
    $alerta = AlertaRonda::factory()->create(['ronda_enfermeria_id' => $ronda->id]);

    Passport::actingAs($usuario);

    expect(Gate::forUser($usuario)->denies('view', $alerta))->toBeTrue()
        ->and(Gate::forUser($usuario)->denies('update', $alerta))->toBeTrue()
        ->and(Gate::forUser($usuario)->denies('viewAny', AlertaRonda::class))->toBeTrue();
});

it('no existe una acción create para alertas de ronda (generadas por el sistema)', function () {
    expect(method_exists(AlertaRondaPolicy::class, 'create'))->toBeFalse();
});
