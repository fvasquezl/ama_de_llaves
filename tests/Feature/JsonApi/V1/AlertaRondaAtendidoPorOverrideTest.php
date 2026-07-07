<?php

use App\Models\AlertaRonda;
use App\Models\RondaEnfermeria;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('marking an alert as attended stamps the acting enfermera as atendido_por', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermera->id]);
    $alerta = AlertaRonda::factory()->create([
        'ronda_enfermeria_id' => $ronda->id,
        'visita_habitacion_id' => null,
        'atendido' => false,
        'atendido_por_id' => null,
    ]);
    Passport::actingAs($enfermera, ['*']);

    $response = $this->patchJson("/api/v1/alertas-ronda/{$alerta->id}", [
        'data' => [
            'type' => 'alertas-ronda',
            'id' => (string) $alerta->id,
            'attributes' => ['atendido' => true],
        ],
    ], jsonApiHeaders());

    $response->assertOk();
    $alerta->refresh();
    expect($alerta->atendido)->toBeTrue()
        ->and($alerta->atendido_por_id)->toBe($enfermera->id);
});

it('cannot spoof atendido_por_id as an enfermera attending her own alert', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $otro = User::factory()->create();
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermera->id]);
    $alerta = AlertaRonda::factory()->create([
        'ronda_enfermeria_id' => $ronda->id,
        'visita_habitacion_id' => null,
        'atendido' => false,
        'atendido_por_id' => null,
    ]);
    Passport::actingAs($enfermera, ['*']);

    $response = $this->patchJson("/api/v1/alertas-ronda/{$alerta->id}", [
        'data' => [
            'type' => 'alertas-ronda',
            'id' => (string) $alerta->id,
            'attributes' => ['atendido' => true],
            'relationships' => [
                'atendidoPor' => ['data' => ['type' => 'users', 'id' => (string) $otro->id]],
            ],
        ],
    ], jsonApiHeaders());

    $response->assertOk();
    expect($alerta->refresh()->atendido_por_id)->toBe($enfermera->id)
        ->and($alerta->atendido_por_id)->not->toBe($otro->id);
});

it('cannot spoof atendido_por_id as a supervisor attending someone elses alert', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $otro = User::factory()->create();
    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermera->id]);
    $alerta = AlertaRonda::factory()->create([
        'ronda_enfermeria_id' => $ronda->id,
        'visita_habitacion_id' => null,
        'atendido' => false,
        'atendido_por_id' => null,
    ]);
    Passport::actingAs($supervisor, ['*']);

    $response = $this->patchJson("/api/v1/alertas-ronda/{$alerta->id}", [
        'data' => [
            'type' => 'alertas-ronda',
            'id' => (string) $alerta->id,
            'attributes' => ['atendido' => true],
            'relationships' => [
                'atendidoPor' => ['data' => ['type' => 'users', 'id' => (string) $otro->id]],
            ],
        ],
    ], jsonApiHeaders());

    $response->assertOk();
    expect($alerta->refresh()->atendido_por_id)->toBe($supervisor->id)
        ->and($alerta->atendido_por_id)->not->toBe($otro->id);
});

it('clears atendido_por_id to null when atendido reverts to false', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermera->id]);
    $alerta = AlertaRonda::factory()->create([
        'ronda_enfermeria_id' => $ronda->id,
        'visita_habitacion_id' => null,
        'atendido' => true,
        'atendido_por_id' => $enfermera->id,
    ]);
    Passport::actingAs($enfermera, ['*']);

    $response = $this->patchJson("/api/v1/alertas-ronda/{$alerta->id}", [
        'data' => [
            'type' => 'alertas-ronda',
            'id' => (string) $alerta->id,
            'attributes' => ['atendido' => false],
        ],
    ], jsonApiHeaders());

    $response->assertOk();
    expect($alerta->refresh()->atendido_por_id)->toBeNull();
});

it('rejects an attempt to change the immutable tipo field', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermera->id]);
    $alerta = AlertaRonda::factory()->create([
        'ronda_enfermeria_id' => $ronda->id,
        'visita_habitacion_id' => null,
        'tipo' => 'visita_tardia',
    ]);
    Passport::actingAs($enfermera, ['*']);

    $response = $this->patchJson("/api/v1/alertas-ronda/{$alerta->id}", [
        'data' => [
            'type' => 'alertas-ronda',
            'id' => (string) $alerta->id,
            'attributes' => ['tipo' => 'visita_omitida'],
        ],
    ], jsonApiHeaders());

    $response->assertStatus(422);
    expect($alerta->refresh()->tipo)->toBe('visita_tardia');
});

it('store is not possible for alertas-ronda', function () {
    // NOTE: `/api/v1/alertas-ronda` IS a registered path (GET index exists
    // there), just not for POST — so Laravel's router raises a standard
    // `MethodNotAllowedHttpException` (405), not a 404. A 404 would only
    // occur for a path that isn't registered for ANY verb (e.g. DELETE on
    // a resource id, which has no matching path template at all). Both
    // outcomes achieve the spec's actual intent — "no store route is
    // registered" — 405 is simply the correct HTTP semantic for this
    // specific case, confirmed against actual routing behavior.
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    Passport::actingAs($enfermera, ['*']);

    $response = $this->postJson('/api/v1/alertas-ronda', [
        'data' => ['type' => 'alertas-ronda', 'attributes' => ['tipo' => 'visita_tardia']],
    ], jsonApiHeaders());

    $response->assertStatus(405);
});
