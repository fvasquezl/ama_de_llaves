<?php

use App\Models\ReporteEnfermeria;
use App\Models\RondaEnfermeria;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

/*
 * Phase 8.4 — full spec-part-10..13 coverage for `reportes-enfermeria` over
 * real HTTP. File-report-as-owner (estado=borrador on create), duplicate-
 * report-for-same-round (422), pre-signed-create rejection (422), sign
 * transition stamping `firmado_at`, and the exactly-409 signed-immutability
 * rule are already covered end-to-end via HTTP in
 * `ReporteEnfermeriaSignedImmutabilityTest.php` (Phase 6) — not duplicated
 * here. This file adds the show/update ownership-authorization (403)
 * scenarios (ownership is by the report's own `enfermera_id`, not the
 * parent round's owner) and the `enfermera` include, which were previously
 * only verified at the Schema/Policy unit level.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('lists only reports authored by the authenticated enfermera', function () {
    $enfermeraA = User::factory()->create();
    $enfermeraA->assignRole('enfermera');
    $enfermeraB = User::factory()->create();
    $enfermeraB->assignRole('enfermera');

    ReporteEnfermeria::factory()->count(2)->create(['enfermera_id' => $enfermeraA->id]);
    ReporteEnfermeria::factory()->count(3)->create(['enfermera_id' => $enfermeraB->id]);
    Passport::actingAs($enfermeraA, ['*']);

    $response = $this->getJson('/api/v1/reportes-enfermeria', jsonApiHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});

it('rejects viewing another enfermeras report with 403 over real http', function () {
    $enfermeraA = User::factory()->create();
    $enfermeraA->assignRole('enfermera');
    $enfermeraB = User::factory()->create();
    $enfermeraB->assignRole('enfermera');
    $reporte = ReporteEnfermeria::factory()->create(['enfermera_id' => $enfermeraB->id]);
    Passport::actingAs($enfermeraA, ['*']);

    $this->getJson("/api/v1/reportes-enfermeria/{$reporte->id}", jsonApiHeaders())
        ->assertStatus(403);
});

it('rejects updating another enfermeras report with 403 over real http', function () {
    $enfermeraA = User::factory()->create();
    $enfermeraA->assignRole('enfermera');
    $enfermeraB = User::factory()->create();
    $enfermeraB->assignRole('enfermera');
    $reporte = ReporteEnfermeria::factory()->create([
        'enfermera_id' => $enfermeraB->id,
        'estado' => 'borrador',
        'observaciones' => 'original',
    ]);
    Passport::actingAs($enfermeraA, ['*']);

    $response = $this->patchJson("/api/v1/reportes-enfermeria/{$reporte->id}", [
        'data' => [
            'type' => 'reportes-enfermeria',
            'id' => (string) $reporte->id,
            'attributes' => ['observaciones' => 'intento de cambio'],
        ],
    ], jsonApiHeaders());

    $response->assertStatus(403);
    expect($reporte->refresh()->observaciones)->toBe('original');
});

it('lets a supervisor view and update a report authored by any enfermera', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $reporte = ReporteEnfermeria::factory()->create([
        'enfermera_id' => $enfermera->id,
        'estado' => 'borrador',
    ]);

    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    Passport::actingAs($supervisor, ['*']);

    $this->getJson("/api/v1/reportes-enfermeria/{$reporte->id}", jsonApiHeaders())->assertOk();

    $response = $this->patchJson("/api/v1/reportes-enfermeria/{$reporte->id}", [
        'data' => [
            'type' => 'reportes-enfermeria',
            'id' => (string) $reporte->id,
            'attributes' => ['observaciones' => 'revisado por supervisor'],
        ],
    ], jsonApiHeaders());

    $response->assertOk();
    expect($reporte->refresh()->observaciones)->toBe('revisado por supervisor');
});

it('includes enfermera via the include query parameter over real http', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermera->id]);
    $reporte = ReporteEnfermeria::factory()->create([
        'ronda_enfermeria_id' => $ronda->id,
        'enfermera_id' => $enfermera->id,
    ]);
    Passport::actingAs($enfermera, ['*']);

    $response = $this->getJson("/api/v1/reportes-enfermeria/{$reporte->id}?include=enfermera", jsonApiHeaders());

    $response->assertOk();
    expect($response->json('included.0.type'))->toBe('users')
        ->and($response->json('included.0.id'))->toBe((string) $enfermera->id);
});

it('filters reportes-enfermeria by estado over real http', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    ReporteEnfermeria::factory()->create(['estado' => 'firmado']);
    ReporteEnfermeria::factory()->create(['estado' => 'borrador']);
    Passport::actingAs($admin, ['*']);

    $response = $this->getJson('/api/v1/reportes-enfermeria?filter[estado]=firmado', jsonApiHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.attributes.estado'))->toBe('firmado');
});
