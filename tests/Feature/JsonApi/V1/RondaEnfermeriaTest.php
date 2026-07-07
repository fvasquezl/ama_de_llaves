<?php

use App\Models\RondaEnfermeria;
use App\Models\User;
use App\Models\VisitaHabitacion;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

/*
 * Phase 8.1 — full spec-part-02..05 coverage for `rondas-enfermeria`,
 * exercised over real HTTP through the mounted routes. Field validation
 * (turno enum, non-UUID enfermera_id, time-window sanity) and the store/
 * create happy path are already covered end-to-end via HTTP in
 * `RondaEnfermeriaValidationTest.php` (Phase 6) — not duplicated here.
 * This file adds the ownership-authorization and relationship-include
 * scenarios that were previously only verified at the Schema/Policy unit
 * level (`IndexQueryOwnershipScopingTest.php`, `RondaEnfermeriaPolicyTest.php`),
 * now driven through the real controller + routing stack.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('lists only the authenticated enfermeras own rounds', function () {
    $enfermeraA = User::factory()->create();
    $enfermeraA->assignRole('enfermera');
    $enfermeraB = User::factory()->create();
    $enfermeraB->assignRole('enfermera');

    RondaEnfermeria::factory()->count(2)->create(['enfermera_id' => $enfermeraA->id]);
    RondaEnfermeria::factory()->count(3)->create(['enfermera_id' => $enfermeraB->id]);
    Passport::actingAs($enfermeraA, ['*']);

    $response = $this->getJson('/api/v1/rondas-enfermeria', jsonApiHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});

it('lets a supervisor list rounds from every enfermera', function () {
    $enfermeraA = User::factory()->create();
    $enfermeraA->assignRole('enfermera');
    $enfermeraB = User::factory()->create();
    $enfermeraB->assignRole('enfermera');

    RondaEnfermeria::factory()->count(2)->create(['enfermera_id' => $enfermeraA->id]);
    RondaEnfermeria::factory()->count(3)->create(['enfermera_id' => $enfermeraB->id]);

    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    Passport::actingAs($supervisor, ['*']);

    $response = $this->getJson('/api/v1/rondas-enfermeria', jsonApiHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(5);
});

it('rejects viewing another enfermeras round with 403 over real http', function () {
    $enfermeraA = User::factory()->create();
    $enfermeraA->assignRole('enfermera');
    $enfermeraB = User::factory()->create();
    $enfermeraB->assignRole('enfermera');
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermeraB->id]);
    Passport::actingAs($enfermeraA, ['*']);

    $this->getJson("/api/v1/rondas-enfermeria/{$ronda->id}", jsonApiHeaders())
        ->assertStatus(403);
});

it('rejects updating another enfermeras round with 403 over real http and leaves it unchanged', function () {
    $enfermeraA = User::factory()->create();
    $enfermeraA->assignRole('enfermera');
    $enfermeraB = User::factory()->create();
    $enfermeraB->assignRole('enfermera');
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermeraB->id, 'notas' => 'original']);
    Passport::actingAs($enfermeraA, ['*']);

    $response = $this->patchJson("/api/v1/rondas-enfermeria/{$ronda->id}", [
        'data' => [
            'type' => 'rondas-enfermeria',
            'id' => (string) $ronda->id,
            'attributes' => ['notas' => 'intento de cambio'],
        ],
    ], jsonApiHeaders());

    $response->assertStatus(403);
    expect($ronda->refresh()->notas)->toBe('original');
});

it('lets an admin view and update a round owned by any enfermera', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermera->id]);

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Passport::actingAs($admin, ['*']);

    $this->getJson("/api/v1/rondas-enfermeria/{$ronda->id}", jsonApiHeaders())->assertOk();

    $response = $this->patchJson("/api/v1/rondas-enfermeria/{$ronda->id}", [
        'data' => [
            'type' => 'rondas-enfermeria',
            'id' => (string) $ronda->id,
            'attributes' => ['notas' => 'revisado por admin'],
        ],
    ], jsonApiHeaders());

    $response->assertOk();
    expect($ronda->refresh()->notas)->toBe('revisado por admin');
});

it('includes visitaHabitacions via the include query parameter over real http', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermera->id]);
    VisitaHabitacion::factory()->count(2)->create(['ronda_enfermeria_id' => $ronda->id]);
    Passport::actingAs($enfermera, ['*']);

    $response = $this->getJson("/api/v1/rondas-enfermeria/{$ronda->id}?include=visitaHabitacions", jsonApiHeaders());

    $response->assertOk();
    expect($response->json('included'))->toHaveCount(2)
        ->and(collect($response->json('included'))->pluck('type')->unique()->all())->toBe(['visitas-habitacion']);
});

it('filters rondas-enfermeria by estado over real http', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    RondaEnfermeria::factory()->create(['estado' => 'completada']);
    RondaEnfermeria::factory()->create(['estado' => 'pendiente']);
    Passport::actingAs($admin, ['*']);

    $response = $this->getJson('/api/v1/rondas-enfermeria?filter[estado]=completada', jsonApiHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.attributes.estado'))->toBe('completada');
});

it('sorts rondas-enfermeria by createdAt over real http', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $older = RondaEnfermeria::factory()->create(['created_at' => now()->subDay()]);
    $newer = RondaEnfermeria::factory()->create(['created_at' => now()]);
    Passport::actingAs($admin, ['*']);

    $response = $this->getJson('/api/v1/rondas-enfermeria?sort=-createdAt', jsonApiHeaders());

    $response->assertOk();
    expect($response->json('data.0.id'))->toBe((string) $newer->id)
        ->and($response->json('data.1.id'))->toBe((string) $older->id);
});
