<?php

use App\Models\Estancia;
use App\Models\Habitacion;
use App\Models\Sucursal;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

/*
 * Phase 4 — spec-part-03/04/05 coverage for the flat/permission-only
 * `habitacions` resource, expanded in place from the minimal, route-less
 * schema originally built for enfermeria-api. Exercised over real HTTP
 * through the newly mounted routes.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('lets a user with habitaciones.crear create a habitacion', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $sucursal = Sucursal::factory()->create();
    Passport::actingAs($admin, ['*']);

    $response = $this->postJson('/api/v1/habitacions', [
        'data' => [
            'type' => 'habitacions',
            'attributes' => [
                'numero' => '204',
                'tipo' => 'doble',
                'piso' => 2,
                'capacidad' => 2,
                'nfc_tag_uid' => 'NFC-ABC-123',
            ],
            'relationships' => [
                'sucursal' => [
                    'data' => ['type' => 'sucursales', 'id' => (string) $sucursal->id],
                ],
            ],
        ],
    ], jsonApiHeaders());

    $response->assertCreated();
    $this->assertDatabaseHas('habitacions', ['numero' => '204', 'sucursal_id' => $sucursal->id]);
});

it('rejects a camarera creating a habitacion (has ver, lacks crear)', function () {
    $camarera = User::factory()->create();
    $camarera->assignRole('camarera');
    $sucursal = Sucursal::factory()->create();
    Passport::actingAs($camarera, ['*']);

    $response = $this->postJson('/api/v1/habitacions', [
        'data' => [
            'type' => 'habitacions',
            'attributes' => [
                'numero' => '305',
                'tipo' => 'individual',
                'piso' => 3,
            ],
            'relationships' => [
                'sucursal' => [
                    'data' => ['type' => 'sucursales', 'id' => (string) $sucursal->id],
                ],
            ],
        ],
    ], jsonApiHeaders());

    $response->assertStatus(403);
    $this->assertDatabaseMissing('habitacions', ['numero' => '305']);
});

it('lets a camarera view but not create rooms (spec-part-05 scenario)', function () {
    $camarera = User::factory()->create();
    $camarera->assignRole('camarera');
    Passport::actingAs($camarera, ['*']);

    $this->getJson('/api/v1/habitacions', jsonApiHeaders())->assertOk();
    $this->postJson('/api/v1/habitacions', [
        'data' => ['type' => 'habitacions', 'attributes' => ['numero' => '1', 'tipo' => 'individual', 'piso' => 1]],
    ], jsonApiHeaders())->assertStatus(403);
});

it('lets a user with habitaciones.editar update a habitacions estado', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $habitacion = Habitacion::factory()->create(['estado' => 'disponible']);
    Passport::actingAs($admin, ['*']);

    $response = $this->patchJson("/api/v1/habitacions/{$habitacion->id}", [
        'data' => [
            'type' => 'habitacions',
            'id' => (string) $habitacion->id,
            'attributes' => ['estado' => 'en_limpieza'],
        ],
    ], jsonApiHeaders());

    $response->assertOk();
    expect($habitacion->refresh()->estado)->toBe('en_limpieza');
});

it('soft-deletes a habitacion on destroy instead of hard-deleting it', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $habitacion = Habitacion::factory()->create();
    Passport::actingAs($admin, ['*']);

    $response = $this->deleteJson("/api/v1/habitacions/{$habitacion->id}", [], jsonApiHeaders());

    $response->assertStatus(204);
    $this->assertSoftDeleted('habitacions', ['id' => $habitacion->id]);
});

it('rejects an invalid tipo with a 422', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $sucursal = Sucursal::factory()->create();
    Passport::actingAs($admin, ['*']);

    $response = $this->postJson('/api/v1/habitacions', [
        'data' => [
            'type' => 'habitacions',
            'attributes' => [
                'numero' => '99',
                'tipo' => 'penthouse',
                'piso' => 9,
            ],
            'relationships' => [
                'sucursal' => [
                    'data' => ['type' => 'sucursales', 'id' => (string) $sucursal->id],
                ],
            ],
        ],
    ], jsonApiHeaders());

    $response->assertStatus(422);
    $response->assertJsonFragment(['source' => ['pointer' => '/data/attributes/tipo']]);
});

it('includes a habitacions sucursal via the include query parameter', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $sucursal = Sucursal::factory()->create();
    $habitacion = Habitacion::factory()->create(['sucursal_id' => $sucursal->id]);
    Passport::actingAs($admin, ['*']);

    $response = $this->getJson("/api/v1/habitacions/{$habitacion->id}?include=sucursal", jsonApiHeaders());

    $response->assertOk();
    expect($response->json('included'))->toHaveCount(1)
        ->and($response->json('included.0.type'))->toBe('sucursales')
        ->and($response->json('included.0.id'))->toBe((string) $sucursal->id);
});

it('includes a habitacions estancias via the include query parameter (Phase 6 forward-declaration closed)', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $habitacion = Habitacion::factory()->create();
    Estancia::factory()->create(['habitacion_id' => $habitacion->id]);
    Passport::actingAs($admin, ['*']);

    $response = $this->getJson("/api/v1/habitacions/{$habitacion->id}?include=estancias", jsonApiHeaders());

    $response->assertOk();
    expect($response->json('included'))->toHaveCount(1)
        ->and($response->json('included.0.type'))->toBe('estancias');
});

it('lets an admin see rooms from both sucursales unfiltered (branch-scoping is explicitly out of scope, spec-part-19)', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $sucursalNorte = Sucursal::factory()->create();
    $sucursalSur = Sucursal::factory()->create();
    $habitacionNorte = Habitacion::factory()->create(['sucursal_id' => $sucursalNorte->id, 'numero' => 'N-1']);
    $habitacionSur = Habitacion::factory()->create(['sucursal_id' => $sucursalSur->id, 'numero' => 'S-1']);
    Passport::actingAs($admin, ['*']);

    $response = $this->getJson('/api/v1/habitacions', jsonApiHeaders());

    $response->assertOk();
    $ids = collect($response->json('data'))->pluck('id')->all();
    expect($response->json('data'))->toHaveCount(2)
        ->and($ids)->toContain((string) $habitacionNorte->id)
        ->and($ids)->toContain((string) $habitacionSur->id);
});

it('filters habitacions by estado and tipo', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Habitacion::factory()->create(['estado' => 'disponible', 'tipo' => 'individual']);
    Habitacion::factory()->create(['estado' => 'ocupada', 'tipo' => 'suite']);
    Passport::actingAs($admin, ['*']);

    $response = $this->getJson('/api/v1/habitacions?filter[estado]=ocupada', jsonApiHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.attributes.tipo'))->toBe('suite');
});
