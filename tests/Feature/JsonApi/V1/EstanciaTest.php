<?php

use App\Models\Estancia;
use App\Models\Habitacion;
use App\Models\Residente;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

/*
 * Phase 6 — spec-part-08/09 coverage for the flat/permission-only
 * `estancias` resource, newly built from zero now that both of its parent
 * domains (Habitacion, Residente) are ready. Exercised over real HTTP
 * through the newly mounted routes. No `destroy` route exists for this
 * resource (the model has no soft-deletes column and the legacy controller
 * never had one either — unchanged, not newly restricted).
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('lets a user with estancias.crear create an estancia', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $residente = Residente::factory()->create();
    $habitacion = Habitacion::factory()->create();
    Passport::actingAs($admin, ['*']);

    $response = $this->postJson('/api/v1/estancias', [
        'data' => [
            'type' => 'estancias',
            'attributes' => [
                'fecha_ingreso' => '2026-01-01',
            ],
            'relationships' => [
                'residente' => [
                    'data' => ['type' => 'residentes', 'id' => (string) $residente->id],
                ],
                'habitacion' => [
                    'data' => ['type' => 'habitacions', 'id' => (string) $habitacion->id],
                ],
            ],
        ],
    ], jsonApiHeaders());

    $response->assertCreated();
    $this->assertDatabaseHas('estancias', [
        'residente_id' => $residente->id,
        'habitacion_id' => $habitacion->id,
        'estado' => 'activa',
    ]);
});

it('rejects a supervisor creating an estancia (has ver, lacks crear)', function () {
    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    $residente = Residente::factory()->create();
    $habitacion = Habitacion::factory()->create();
    Passport::actingAs($supervisor, ['*']);

    $response = $this->postJson('/api/v1/estancias', [
        'data' => [
            'type' => 'estancias',
            'attributes' => ['fecha_ingreso' => '2026-01-01'],
            'relationships' => [
                'residente' => ['data' => ['type' => 'residentes', 'id' => (string) $residente->id]],
                'habitacion' => ['data' => ['type' => 'habitacions', 'id' => (string) $habitacion->id]],
            ],
        ],
    ], jsonApiHeaders());

    $response->assertStatus(403);
    $this->assertDatabaseMissing('estancias', ['residente_id' => $residente->id]);
});

it('lets an enfermera create an estancia (spec-part-09 scenario, Phase 10 seeder gap-fix)', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $residente = Residente::factory()->create();
    $habitacion = Habitacion::factory()->create();
    Passport::actingAs($enfermera, ['*']);

    $response = $this->postJson('/api/v1/estancias', [
        'data' => [
            'type' => 'estancias',
            'attributes' => ['fecha_ingreso' => '2026-01-01'],
            'relationships' => [
                'residente' => ['data' => ['type' => 'residentes', 'id' => (string) $residente->id]],
                'habitacion' => ['data' => ['type' => 'habitacions', 'id' => (string) $habitacion->id]],
            ],
        ],
    ], jsonApiHeaders());

    $response->assertCreated();
    $this->assertDatabaseHas('estancias', [
        'residente_id' => $residente->id,
        'habitacion_id' => $habitacion->id,
    ]);
});

it('lets a supervisor view but not create estancias (spec-part-09 scenario)', function () {
    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    Passport::actingAs($supervisor, ['*']);

    $this->getJson('/api/v1/estancias', jsonApiHeaders())->assertOk();
    $this->postJson('/api/v1/estancias', [
        'data' => ['type' => 'estancias', 'attributes' => ['fecha_ingreso' => '2026-01-01']],
    ], jsonApiHeaders())->assertStatus(403);
});

it('lets a user with estancias.editar update an estancias estado', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $estancia = Estancia::factory()->create(['estado' => 'activa']);
    Passport::actingAs($admin, ['*']);

    $response = $this->patchJson("/api/v1/estancias/{$estancia->id}", [
        'data' => [
            'type' => 'estancias',
            'id' => (string) $estancia->id,
            'attributes' => ['estado' => 'alta'],
        ],
    ], jsonApiHeaders());

    $response->assertOk();
    expect($estancia->refresh()->estado)->toBe('alta');
});

it('rejects a fecha_egreso earlier than fecha_ingreso with a 422', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $residente = Residente::factory()->create();
    $habitacion = Habitacion::factory()->create();
    Passport::actingAs($admin, ['*']);

    $response = $this->postJson('/api/v1/estancias', [
        'data' => [
            'type' => 'estancias',
            'attributes' => [
                'fecha_ingreso' => '2026-01-10',
                'fecha_egreso' => '2026-01-01',
            ],
            'relationships' => [
                'residente' => ['data' => ['type' => 'residentes', 'id' => (string) $residente->id]],
                'habitacion' => ['data' => ['type' => 'habitacions', 'id' => (string) $habitacion->id]],
            ],
        ],
    ], jsonApiHeaders());

    $response->assertStatus(422);
    $response->assertJsonFragment(['source' => ['pointer' => '/data/attributes/fecha_egreso']]);
});

it('rejects an invalid estado with a 422', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $residente = Residente::factory()->create();
    $habitacion = Habitacion::factory()->create();
    Passport::actingAs($admin, ['*']);

    $response = $this->postJson('/api/v1/estancias', [
        'data' => [
            'type' => 'estancias',
            'attributes' => [
                'fecha_ingreso' => '2026-01-01',
                'estado' => 'no-existe',
            ],
            'relationships' => [
                'residente' => ['data' => ['type' => 'residentes', 'id' => (string) $residente->id]],
                'habitacion' => ['data' => ['type' => 'habitacions', 'id' => (string) $habitacion->id]],
            ],
        ],
    ], jsonApiHeaders());

    $response->assertStatus(422);
    $response->assertJsonFragment(['source' => ['pointer' => '/data/attributes/estado']]);
});

it('includes both residente and habitacion via the include query parameter', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $residente = Residente::factory()->create();
    $habitacion = Habitacion::factory()->create();
    $estancia = Estancia::factory()->create([
        'residente_id' => $residente->id,
        'habitacion_id' => $habitacion->id,
    ]);
    Passport::actingAs($admin, ['*']);

    $response = $this->getJson("/api/v1/estancias/{$estancia->id}?include=residente,habitacion", jsonApiHeaders());

    $response->assertOk();
    $types = collect($response->json('included'))->pluck('type')->sort()->values()->all();
    expect($types)->toBe(['habitacions', 'residentes']);
});

it('filters estancias by estado', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Estancia::factory()->create(['estado' => 'activa']);
    Estancia::factory()->create(['estado' => 'alta']);
    Passport::actingAs($admin, ['*']);

    $response = $this->getJson('/api/v1/estancias?filter[estado]=alta', jsonApiHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.attributes.estado'))->toBe('alta');
});

it('returns 405 for DELETE on estancias — no destroy route is registered (spec-part-08)', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $estancia = Estancia::factory()->create();
    Passport::actingAs($admin, ['*']);

    // The id-scoped path IS registered (for GET/PATCH), so Laravel's router
    // raises MethodNotAllowedHttpException (405) rather than a bare
    // "route not found" 404 — same documented deviation as RouteExposureTest.
    $this->deleteJson("/api/v1/estancias/{$estancia->id}", [], jsonApiHeaders())
        ->assertStatus(405);
});
