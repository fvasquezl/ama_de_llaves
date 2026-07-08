<?php

use App\Models\ChecklistEnfermeriaItem;
use App\Models\Habitacion;
use App\Models\Residente;
use App\Models\RondaEnfermeria;
use App\Models\User;
use App\Models\VisitaHabitacion;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

/*
 * Phase 8.2 — full spec-part-06..08 coverage for `visitas-habitacion` over
 * real HTTP. Create-under-own-round success, create-under-another-round
 * (422) rejection, nonexistent habitacion_id (404), and the NFC
 * auto-stamp/inconsistency rules are already covered end-to-end via HTTP in
 * `VisitaHabitacionValidationTest.php` (Phase 6) — not duplicated here.
 * This file adds the show/update ownership-authorization (403) scenarios
 * and the `checklistEnfermeriaItems` include, which were previously only
 * verified at the Schema/Policy unit level.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('lists only visits under the authenticated enfermeras own rounds', function () {
    $enfermeraA = User::factory()->create();
    $enfermeraA->assignRole('enfermera');
    $enfermeraB = User::factory()->create();
    $enfermeraB->assignRole('enfermera');
    $rondaA = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermeraA->id]);
    $rondaB = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermeraB->id]);

    VisitaHabitacion::factory()->count(2)->create(['ronda_enfermeria_id' => $rondaA->id]);
    VisitaHabitacion::factory()->count(3)->create(['ronda_enfermeria_id' => $rondaB->id]);
    Passport::actingAs($enfermeraA, ['*']);

    $response = $this->getJson('/api/v1/visitas-habitacion', jsonApiHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});

it('rejects viewing a visit under another enfermeras round with 403 over real http', function () {
    $enfermeraA = User::factory()->create();
    $enfermeraA->assignRole('enfermera');
    $enfermeraB = User::factory()->create();
    $enfermeraB->assignRole('enfermera');
    $rondaB = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermeraB->id]);
    $visita = VisitaHabitacion::factory()->create(['ronda_enfermeria_id' => $rondaB->id]);
    Passport::actingAs($enfermeraA, ['*']);

    $this->getJson("/api/v1/visitas-habitacion/{$visita->id}", jsonApiHeaders())
        ->assertStatus(403);
});

it('rejects updating a visit under another enfermeras round with 403 over real http', function () {
    $enfermeraA = User::factory()->create();
    $enfermeraA->assignRole('enfermera');
    $enfermeraB = User::factory()->create();
    $enfermeraB->assignRole('enfermera');
    $rondaB = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermeraB->id]);
    $visita = VisitaHabitacion::factory()->create(['ronda_enfermeria_id' => $rondaB->id, 'notas' => 'original']);
    Passport::actingAs($enfermeraA, ['*']);

    $response = $this->patchJson("/api/v1/visitas-habitacion/{$visita->id}", [
        'data' => [
            'type' => 'visitas-habitacion',
            'id' => (string) $visita->id,
            'attributes' => ['notas' => 'intento de cambio'],
        ],
    ], jsonApiHeaders());

    $response->assertStatus(403);
    expect($visita->refresh()->notas)->toBe('original');
});

it('lets a supervisor view and update a visit under any enfermeras round', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermera->id]);
    $visita = VisitaHabitacion::factory()->create(['ronda_enfermeria_id' => $ronda->id]);

    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    Passport::actingAs($supervisor, ['*']);

    $this->getJson("/api/v1/visitas-habitacion/{$visita->id}", jsonApiHeaders())->assertOk();

    $response = $this->patchJson("/api/v1/visitas-habitacion/{$visita->id}", [
        'data' => [
            'type' => 'visitas-habitacion',
            'id' => (string) $visita->id,
            'attributes' => ['notas' => 'revisado por supervisor'],
        ],
    ], jsonApiHeaders());

    $response->assertOk();
    expect($visita->refresh()->notas)->toBe('revisado por supervisor');
});

it('includes checklistEnfermeriaItems via the include query parameter over real http', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermera->id]);
    $visita = VisitaHabitacion::factory()->create(['ronda_enfermeria_id' => $ronda->id]);
    ChecklistEnfermeriaItem::factory()->count(3)->create(['visita_habitacion_id' => $visita->id]);
    Passport::actingAs($enfermera, ['*']);

    $response = $this->getJson("/api/v1/visitas-habitacion/{$visita->id}?include=checklistEnfermeriaItems", jsonApiHeaders());

    $response->assertOk();
    expect($response->json('included'))->toHaveCount(3)
        ->and(collect($response->json('included'))->pluck('type')->unique()->all())->toBe(['checklist-enfermeria']);
});

it('still resolves the habitacion include now that HabitacionSchema is fully expanded (non-regression, spec-part-05)', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermera->id]);
    $habitacion = Habitacion::factory()->create(['numero' => '101']);
    $visita = VisitaHabitacion::factory()->create([
        'ronda_enfermeria_id' => $ronda->id,
        'habitacion_id' => $habitacion->id,
    ]);
    Passport::actingAs($enfermera, ['*']);

    $response = $this->getJson("/api/v1/visitas-habitacion/{$visita->id}?include=habitacion", jsonApiHeaders());

    $response->assertOk();
    expect($response->json('included'))->toHaveCount(1);
    $included = $response->json('included.0');
    expect($included['type'])->toBe('habitacions')
        ->and($included['id'])->toBe((string) $habitacion->id)
        ->and($included['attributes']['numero'])->toBe('101');
});

it('still resolves the residente include now that ResidenteSchema is fully expanded (non-regression, spec-part-07)', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermera->id]);
    $residente = Residente::factory()->create(['nombre' => 'Maria']);
    $visita = VisitaHabitacion::factory()->create([
        'ronda_enfermeria_id' => $ronda->id,
        'residente_id' => $residente->id,
    ]);
    Passport::actingAs($enfermera, ['*']);

    $response = $this->getJson("/api/v1/visitas-habitacion/{$visita->id}?include=residente", jsonApiHeaders());

    $response->assertOk();
    expect($response->json('included'))->toHaveCount(1);
    $included = $response->json('included.0');
    expect($included['type'])->toBe('residentes')
        ->and($included['id'])->toBe((string) $residente->id)
        ->and($included['attributes']['nombre'])->toBe('Maria');
});

it('resolves both habitacion and residente together via a combined include (non-regression, task 11.18)', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermera->id]);
    $habitacion = Habitacion::factory()->create(['numero' => '202']);
    $residente = Residente::factory()->create(['nombre' => 'Lucia']);
    $visita = VisitaHabitacion::factory()->create([
        'ronda_enfermeria_id' => $ronda->id,
        'habitacion_id' => $habitacion->id,
        'residente_id' => $residente->id,
    ]);
    Passport::actingAs($enfermera, ['*']);

    $response = $this->getJson("/api/v1/visitas-habitacion/{$visita->id}?include=habitacion,residente", jsonApiHeaders());

    $response->assertOk();
    $included = collect($response->json('included'))->keyBy('type');
    expect($included)->toHaveCount(2)
        ->and($included['habitacions']['attributes']['numero'])->toBe('202')
        ->and($included['residentes']['attributes']['nombre'])->toBe('Lucia');
});

it('filters visitas-habitacion by nfc_verificado over real http', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    VisitaHabitacion::factory()->create(['nfc_verificado' => true, 'nfc_escaneado_at' => now()]);
    VisitaHabitacion::factory()->create(['nfc_verificado' => false, 'nfc_escaneado_at' => null]);
    Passport::actingAs($admin, ['*']);

    $response = $this->getJson('/api/v1/visitas-habitacion?filter[nfc_verificado]=true', jsonApiHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.attributes.nfc_verificado'))->toBeTrue();
});
