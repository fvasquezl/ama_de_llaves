<?php

use App\Models\AlertaRonda;
use App\Models\RondaEnfermeria;
use App\Models\User;
use App\Models\VisitaHabitacion;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

/*
 * Phase 8.5 — full spec-part-14..17 coverage for `alertas-ronda` over real
 * HTTP. The `atendido`/`atendido_por_id` server-override consistency rules
 * and the immutable-`tipo` rejection are already covered end-to-end via
 * HTTP in `AlertaRondaAtendidoPorOverrideTest.php` (Phase 6, including the
 * documented store-returns-405-not-404 deviation) — not duplicated here.
 * This file adds the show/update ownership-authorization (403) scenarios
 * across the one-hop `rondaEnfermeria.enfermera_id` chain and the
 * `rondaEnfermeria,atendidoPor` include, which were previously only
 * verified at the Schema/Policy unit level.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('lists only alerts belonging to the authenticated enfermeras own rounds', function () {
    $enfermeraA = User::factory()->create();
    $enfermeraA->assignRole('enfermera');
    $enfermeraB = User::factory()->create();
    $enfermeraB->assignRole('enfermera');
    $rondaA = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermeraA->id]);
    $rondaB = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermeraB->id]);

    AlertaRonda::factory()->count(2)->create(['ronda_enfermeria_id' => $rondaA->id, 'visita_habitacion_id' => null]);
    AlertaRonda::factory()->count(3)->create(['ronda_enfermeria_id' => $rondaB->id, 'visita_habitacion_id' => null]);
    Passport::actingAs($enfermeraA, ['*']);

    $response = $this->getJson('/api/v1/alertas-ronda', jsonApiHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});

it('rejects viewing an alert on another enfermeras round with 403 over real http', function () {
    $enfermeraA = User::factory()->create();
    $enfermeraA->assignRole('enfermera');
    $enfermeraB = User::factory()->create();
    $enfermeraB->assignRole('enfermera');
    $rondaB = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermeraB->id]);
    $alerta = AlertaRonda::factory()->create(['ronda_enfermeria_id' => $rondaB->id, 'visita_habitacion_id' => null]);
    Passport::actingAs($enfermeraA, ['*']);

    $this->getJson("/api/v1/alertas-ronda/{$alerta->id}", jsonApiHeaders())
        ->assertStatus(403);
});

it('rejects updating an alert on another enfermeras round with 403 over real http', function () {
    $enfermeraA = User::factory()->create();
    $enfermeraA->assignRole('enfermera');
    $enfermeraB = User::factory()->create();
    $enfermeraB->assignRole('enfermera');
    $rondaB = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermeraB->id]);
    $alerta = AlertaRonda::factory()->create([
        'ronda_enfermeria_id' => $rondaB->id,
        'visita_habitacion_id' => null,
        'atendido' => false,
        'atendido_por_id' => null,
    ]);
    Passport::actingAs($enfermeraA, ['*']);

    $response = $this->patchJson("/api/v1/alertas-ronda/{$alerta->id}", [
        'data' => [
            'type' => 'alertas-ronda',
            'id' => (string) $alerta->id,
            'attributes' => ['atendido' => true],
        ],
    ], jsonApiHeaders());

    $response->assertStatus(403);
    expect($alerta->refresh()->atendido)->toBeFalse();
});

it('lets an admin view and update an alert on any enfermeras round', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermera->id]);
    $alerta = AlertaRonda::factory()->create([
        'ronda_enfermeria_id' => $ronda->id,
        'visita_habitacion_id' => null,
        'atendido' => false,
        'atendido_por_id' => null,
    ]);

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Passport::actingAs($admin, ['*']);

    $this->getJson("/api/v1/alertas-ronda/{$alerta->id}", jsonApiHeaders())->assertOk();

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
        ->and($alerta->atendido_por_id)->toBe($admin->id);
});

it('includes rondaEnfermeria and atendidoPor via the include query parameter over real http', function () {
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

    $response = $this->getJson("/api/v1/alertas-ronda/{$alerta->id}?include=rondaEnfermeria,atendidoPor", jsonApiHeaders());

    $response->assertOk();
    $types = collect($response->json('included'))->pluck('type')->sort()->values()->all();
    expect($types)->toBe(['rondas-enfermeria', 'users']);
});

it('includes visitaHabitacion when present via the include query parameter over real http', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermera->id]);
    $visita = VisitaHabitacion::factory()->create(['ronda_enfermeria_id' => $ronda->id]);
    $alerta = AlertaRonda::factory()->create([
        'ronda_enfermeria_id' => $ronda->id,
        'visita_habitacion_id' => $visita->id,
    ]);
    Passport::actingAs($enfermera, ['*']);

    $response = $this->getJson("/api/v1/alertas-ronda/{$alerta->id}?include=visitaHabitacion", jsonApiHeaders());

    $response->assertOk();
    expect($response->json('included.0.type'))->toBe('visitas-habitacion')
        ->and($response->json('included.0.id'))->toBe((string) $visita->id);
});

it('filters alertas-ronda by atendido over real http', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    AlertaRonda::factory()->create(['atendido' => true]);
    AlertaRonda::factory()->create(['atendido' => false]);
    Passport::actingAs($admin, ['*']);

    $response = $this->getJson('/api/v1/alertas-ronda?filter[atendido]=true', jsonApiHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
});
