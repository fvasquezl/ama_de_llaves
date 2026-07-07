<?php

use App\Models\ChecklistEnfermeriaItem;
use App\Models\RondaEnfermeria;
use App\Models\User;
use App\Models\VisitaHabitacion;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

/*
 * Phase 8.3 — full spec-part-09/10 coverage for `checklist-enfermeria` over
 * real HTTP. Add-item-to-own-visit success, add-item-to-unowned-visit's-
 * chain rejection (422), missing descripcion (422), and negative orden
 * (422) are already covered end-to-end via HTTP in
 * `ChecklistEnfermeriaItemValidationTest.php` (Phase 6) — not duplicated
 * here. This file adds the show/update ownership-authorization (403)
 * scenarios across the two-hop chain and the `visitaHabitacion` include,
 * which were previously only verified at the Schema/Policy unit level.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('lists only checklist items whose two-hop chain resolves to the authenticated enfermera', function () {
    $enfermeraA = User::factory()->create();
    $enfermeraA->assignRole('enfermera');
    $enfermeraB = User::factory()->create();
    $enfermeraB->assignRole('enfermera');
    $rondaA = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermeraA->id]);
    $rondaB = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermeraB->id]);
    $visitaA = VisitaHabitacion::factory()->create(['ronda_enfermeria_id' => $rondaA->id]);
    $visitaB = VisitaHabitacion::factory()->create(['ronda_enfermeria_id' => $rondaB->id]);

    ChecklistEnfermeriaItem::factory()->count(2)->create(['visita_habitacion_id' => $visitaA->id]);
    ChecklistEnfermeriaItem::factory()->count(4)->create(['visita_habitacion_id' => $visitaB->id]);
    Passport::actingAs($enfermeraA, ['*']);

    $response = $this->getJson('/api/v1/checklist-enfermeria', jsonApiHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});

it('rejects viewing a checklist item under another enfermeras chain with 403 over real http', function () {
    $enfermeraA = User::factory()->create();
    $enfermeraA->assignRole('enfermera');
    $enfermeraB = User::factory()->create();
    $enfermeraB->assignRole('enfermera');
    $rondaB = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermeraB->id]);
    $visitaB = VisitaHabitacion::factory()->create(['ronda_enfermeria_id' => $rondaB->id]);
    $item = ChecklistEnfermeriaItem::factory()->create(['visita_habitacion_id' => $visitaB->id]);
    Passport::actingAs($enfermeraA, ['*']);

    $this->getJson("/api/v1/checklist-enfermeria/{$item->id}", jsonApiHeaders())
        ->assertStatus(403);
});

it('rejects updating a checklist item under another enfermeras chain with 403 over real http', function () {
    $enfermeraA = User::factory()->create();
    $enfermeraA->assignRole('enfermera');
    $enfermeraB = User::factory()->create();
    $enfermeraB->assignRole('enfermera');
    $rondaB = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermeraB->id]);
    $visitaB = VisitaHabitacion::factory()->create(['ronda_enfermeria_id' => $rondaB->id]);
    $item = ChecklistEnfermeriaItem::factory()->create(['visita_habitacion_id' => $visitaB->id, 'completado' => false]);
    Passport::actingAs($enfermeraA, ['*']);

    $response = $this->patchJson("/api/v1/checklist-enfermeria/{$item->id}", [
        'data' => [
            'type' => 'checklist-enfermeria',
            'id' => (string) $item->id,
            'attributes' => ['completado' => true],
        ],
    ], jsonApiHeaders());

    $response->assertStatus(403);
    expect($item->refresh()->completado)->toBeFalse();
});

it('lets an admin view and update a checklist item under any enfermeras chain', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermera->id]);
    $visita = VisitaHabitacion::factory()->create(['ronda_enfermeria_id' => $ronda->id]);
    $item = ChecklistEnfermeriaItem::factory()->create(['visita_habitacion_id' => $visita->id, 'completado' => false]);

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Passport::actingAs($admin, ['*']);

    $this->getJson("/api/v1/checklist-enfermeria/{$item->id}", jsonApiHeaders())->assertOk();

    $response = $this->patchJson("/api/v1/checklist-enfermeria/{$item->id}", [
        'data' => [
            'type' => 'checklist-enfermeria',
            'id' => (string) $item->id,
            'attributes' => ['completado' => true],
        ],
    ], jsonApiHeaders());

    $response->assertOk();
    expect($item->refresh()->completado)->toBeTrue();
});

it('includes visitaHabitacion via the include query parameter over real http', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermera->id]);
    $visita = VisitaHabitacion::factory()->create(['ronda_enfermeria_id' => $ronda->id]);
    $item = ChecklistEnfermeriaItem::factory()->create(['visita_habitacion_id' => $visita->id]);
    Passport::actingAs($enfermera, ['*']);

    $response = $this->getJson("/api/v1/checklist-enfermeria/{$item->id}?include=visitaHabitacion", jsonApiHeaders());

    $response->assertOk();
    expect($response->json('included.0.type'))->toBe('visitas-habitacion')
        ->and($response->json('included.0.id'))->toBe((string) $visita->id);
});

it('filters checklist-enfermeria by completado over real http', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    ChecklistEnfermeriaItem::factory()->create(['completado' => true]);
    ChecklistEnfermeriaItem::factory()->create(['completado' => false]);
    Passport::actingAs($admin, ['*']);

    $response = $this->getJson('/api/v1/checklist-enfermeria?filter[completado]=true', jsonApiHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
});
