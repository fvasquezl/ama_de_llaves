<?php

use App\Models\Habitacion;
use App\Models\ReporteMantenimiento;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

/*
 * Phase 9 — spec-part-17/18/20 coverage for the ownership-scoped
 * `reportes-mantenimiento` resource, exercised over real HTTP through the
 * newly mounted routes. Owner FK is `reportado_por_id` (direct). No
 * `destroy` route exists for this resource (decision 1 — `.eliminar`
 * dropped entirely, no soft-deletes column).
 *
 * The seeded `camarera` role grants `reportes-mantenimiento.ver` (Phase 10,
 * task 10.4 — a real behavior change from the legacy fire-and-forget-only
 * capability: a camarera can now list/view only the reports she personally
 * filed), so tests below rely on the seeded role directly.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('lists only the authenticated camareras own filed reports (new .ver capability, gap fix)', function () {
    $camareraA = User::factory()->create();
    $camareraA->assignRole('camarera');
    $camareraB = User::factory()->create();
    $camareraB->assignRole('camarera');

    ReporteMantenimiento::factory()->count(2)->create(['reportado_por_id' => $camareraA->id]);
    ReporteMantenimiento::factory()->count(3)->create(['reportado_por_id' => $camareraB->id]);
    Passport::actingAs($camareraA, ['*']);

    $response = $this->getJson('/api/v1/reportes-mantenimiento', jsonApiHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});

it('lets a supervisor and an admin list reports from every camarera', function () {
    $camareraA = User::factory()->create();
    $camareraA->assignRole('camarera');
    $camareraB = User::factory()->create();
    $camareraB->assignRole('camarera');

    ReporteMantenimiento::factory()->count(2)->create(['reportado_por_id' => $camareraA->id]);
    ReporteMantenimiento::factory()->count(3)->create(['reportado_por_id' => $camareraB->id]);

    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    Passport::actingAs($supervisor, ['*']);

    $this->getJson('/api/v1/reportes-mantenimiento', jsonApiHeaders())
        ->assertOk()
        ->assertJsonCount(5, 'data');

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Passport::actingAs($admin, ['*']);

    $this->getJson('/api/v1/reportes-mantenimiento', jsonApiHeaders())
        ->assertOk()
        ->assertJsonCount(5, 'data');
});

it('rejects a camarera viewing another camareras report with 403 over real http', function () {
    $camareraA = User::factory()->create();
    $camareraA->assignRole('camarera');
    $camareraB = User::factory()->create();
    $camareraB->assignRole('camarera');
    $reporte = ReporteMantenimiento::factory()->create(['reportado_por_id' => $camareraB->id]);
    Passport::actingAs($camareraA, ['*']);

    $this->getJson("/api/v1/reportes-mantenimiento/{$reporte->id}", jsonApiHeaders())
        ->assertStatus(403);
});

it('lets a camarera view her own filed report', function () {
    $camarera = User::factory()->create();
    $camarera->assignRole('camarera');
    $reporte = ReporteMantenimiento::factory()->create(['reportado_por_id' => $camarera->id]);
    Passport::actingAs($camarera, ['*']);

    $this->getJson("/api/v1/reportes-mantenimiento/{$reporte->id}", jsonApiHeaders())
        ->assertOk();
});

it('lets a camarera create a maintenance report (fire-and-forget)', function () {
    $camarera = User::factory()->create();
    $camarera->assignRole('camarera');
    $habitacion = Habitacion::factory()->create();
    Passport::actingAs($camarera, ['*']);

    $response = $this->postJson('/api/v1/reportes-mantenimiento', [
        'data' => [
            'type' => 'reportes-mantenimiento',
            'attributes' => [
                'descripcion' => 'Fuga de agua en el baño',
            ],
            'relationships' => [
                'habitacion' => ['data' => ['type' => 'habitacions', 'id' => (string) $habitacion->id]],
                'reportadoPor' => ['data' => ['type' => 'users', 'id' => (string) $camarera->id]],
            ],
        ],
    ], jsonApiHeaders());

    $response->assertCreated();
    $this->assertDatabaseHas('reporte_mantenimientos', [
        'habitacion_id' => $habitacion->id,
        'reportado_por_id' => $camarera->id,
        'descripcion' => 'Fuga de agua en el baño',
    ]);
});

it('rejects missing descripcion with a 422', function () {
    $camarera = User::factory()->create();
    $camarera->assignRole('camarera');
    $habitacion = Habitacion::factory()->create();
    Passport::actingAs($camarera, ['*']);

    $response = $this->postJson('/api/v1/reportes-mantenimiento', [
        'data' => [
            'type' => 'reportes-mantenimiento',
            'relationships' => [
                'habitacion' => ['data' => ['type' => 'habitacions', 'id' => (string) $habitacion->id]],
                'reportadoPor' => ['data' => ['type' => 'users', 'id' => (string) $camarera->id]],
            ],
        ],
    ], jsonApiHeaders());

    $response->assertStatus(422);
    $response->assertJsonFragment(['source' => ['pointer' => '/data/attributes/descripcion']]);
});

it('lets an admin update a report owned by a camarera (cross-cutting elevated-role bypass)', function () {
    $camarera = User::factory()->create();
    $camarera->assignRole('camarera');
    $reporte = ReporteMantenimiento::factory()->create(['reportado_por_id' => $camarera->id, 'estado' => 'pendiente']);

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Passport::actingAs($admin, ['*']);

    $response = $this->patchJson("/api/v1/reportes-mantenimiento/{$reporte->id}", [
        'data' => [
            'type' => 'reportes-mantenimiento',
            'id' => (string) $reporte->id,
            'attributes' => ['estado' => 'resuelto', 'notas_resolucion' => 'Reparado por mantenimiento'],
        ],
    ], jsonApiHeaders());

    $response->assertOk();
    expect($reporte->refresh())
        ->estado->toBe('resuelto')
        ->notas_resolucion->toBe('Reparado por mantenimiento');
});

it('includes the reporting user via the include query parameter', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $camarera = User::factory()->create();
    $camarera->assignRole('camarera');
    $reporte = ReporteMantenimiento::factory()->create(['reportado_por_id' => $camarera->id]);
    Passport::actingAs($admin, ['*']);

    $response = $this->getJson("/api/v1/reportes-mantenimiento/{$reporte->id}?include=reportadoPor", jsonApiHeaders());

    $response->assertOk();
    expect($response->json('included'))->toHaveCount(1)
        ->and($response->json('included.0.type'))->toBe('users')
        ->and($response->json('included.0.id'))->toBe((string) $camarera->id);
});

it('filters reportes-mantenimiento by estado over real http', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    ReporteMantenimiento::factory()->create(['estado' => 'resuelto']);
    ReporteMantenimiento::factory()->create(['estado' => 'pendiente']);
    Passport::actingAs($admin, ['*']);

    $response = $this->getJson('/api/v1/reportes-mantenimiento?filter[estado]=resuelto', jsonApiHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.attributes.estado'))->toBe('resuelto');
});

it('returns 405 for DELETE on reportes-mantenimiento — no destroy route is registered (spec-part-17)', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $reporte = ReporteMantenimiento::factory()->create();
    Passport::actingAs($admin, ['*']);

    // The id-scoped path IS registered (for GET/PATCH), so Laravel's router
    // raises MethodNotAllowedHttpException (405) rather than a bare
    // "route not found" 404 — same documented deviation as
    // TareaLimpiezaTest/EstanciaTest.
    $this->deleteJson("/api/v1/reportes-mantenimiento/{$reporte->id}", [], jsonApiHeaders())
        ->assertStatus(405);
});
