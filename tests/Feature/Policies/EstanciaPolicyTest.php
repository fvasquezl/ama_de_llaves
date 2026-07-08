<?php

use App\Models\Estancia;
use App\Models\User;
use App\Policies\EstanciaPolicy;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('permite a admin ver, crear y editar cualquier estancia', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $estancia = Estancia::factory()->create();

    Passport::actingAs($admin);

    expect(Gate::forUser($admin)->allows('viewAny', Estancia::class))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('view', $estancia))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('create', Estancia::class))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('update', $estancia))->toBeTrue();
});

it('permite a supervisor ver pero no crear o editar una estancia', function () {
    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    $estancia = Estancia::factory()->create();

    Passport::actingAs($supervisor);

    expect(Gate::forUser($supervisor)->allows('viewAny', Estancia::class))->toBeTrue()
        ->and(Gate::forUser($supervisor)->allows('view', $estancia))->toBeTrue()
        ->and(Gate::forUser($supervisor)->denies('create', Estancia::class))->toBeTrue()
        ->and(Gate::forUser($supervisor)->denies('update', $estancia))->toBeTrue();
});

it('permite a enfermera crear una estancia (spec-part-09, Phase 10 seeder gap-fix)', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');

    Passport::actingAs($enfermera);

    expect(Gate::forUser($enfermera)->allows('create', Estancia::class))->toBeTrue();
});

it('niega a un usuario sin estancias.ver ver o listar estancias', function () {
    $usuario = User::factory()->create();
    $estancia = Estancia::factory()->create();

    Passport::actingAs($usuario);

    expect(Gate::forUser($usuario)->denies('viewAny', Estancia::class))->toBeTrue()
        ->and(Gate::forUser($usuario)->denies('view', $estancia))->toBeTrue();
});

it('no existe una acción delete para estancias (el recurso no tiene ruta destroy)', function () {
    expect(method_exists(EstanciaPolicy::class, 'delete'))->toBeFalse();
});
