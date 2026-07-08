<?php

use App\Models\Habitacion;
use App\Models\Sucursal;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

/*
 * Phase 3 — spec-part-01/02/03 coverage for the flat/permission-only
 * `sucursales` resource, exercised over real HTTP through the mounted
 * routes.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('lets an admin create a sucursal', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Passport::actingAs($admin, ['*']);

    $response = $this->postJson('/api/v1/sucursales', [
        'data' => [
            'type' => 'sucursales',
            'attributes' => [
                'nombre' => 'Sede Norte',
                'direccion' => 'Av. Siempre Viva 123',
                'ciudad' => 'CDMX',
                'telefono' => '5555555555',
                'email' => 'norte@amadellaves.test',
                'activa' => true,
            ],
        ],
    ], jsonApiHeaders());

    $response->assertCreated();
    $this->assertDatabaseHas('sucursals', ['nombre' => 'Sede Norte']);
});

it('rejects a non-admin creating a sucursal', function () {
    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    Passport::actingAs($supervisor, ['*']);

    $response = $this->postJson('/api/v1/sucursales', [
        'data' => [
            'type' => 'sucursales',
            'attributes' => [
                'nombre' => 'Sede Sur',
                'direccion' => 'Calle Falsa 456',
                'ciudad' => 'GDL',
            ],
        ],
    ], jsonApiHeaders());

    $response->assertStatus(403);
    $this->assertDatabaseMissing('sucursals', ['nombre' => 'Sede Sur']);
});

it('soft-deletes a sucursal on destroy instead of hard-deleting it', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $sucursal = Sucursal::factory()->create();
    Passport::actingAs($admin, ['*']);

    $response = $this->deleteJson("/api/v1/sucursales/{$sucursal->id}", [], jsonApiHeaders());

    $response->assertStatus(204);
    $this->assertSoftDeleted('sucursals', ['id' => $sucursal->id]);
});

it('rejects a duplicate email with a 422', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Sucursal::factory()->create(['email' => 'a@b.com']);
    Passport::actingAs($admin, ['*']);

    $response = $this->postJson('/api/v1/sucursales', [
        'data' => [
            'type' => 'sucursales',
            'attributes' => [
                'nombre' => 'Sede Duplicada',
                'direccion' => 'Calle X 1',
                'ciudad' => 'MTY',
                'email' => 'a@b.com',
            ],
        ],
    ], jsonApiHeaders());

    $response->assertStatus(422);
    $response->assertJsonFragment(['source' => ['pointer' => '/data/attributes/email']]);
});

it('includes a sucursals habitaciones via the include query parameter', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $sucursal = Sucursal::factory()->create();
    Habitacion::factory()->count(2)->create(['sucursal_id' => $sucursal->id]);
    Passport::actingAs($admin, ['*']);

    // The relation field is named `habitacions` (matching the `Sucursal`
    // model's `habitacions()` relation method), not the grammatically
    // correct Spanish plural `habitaciones` — see design-part-11.
    $response = $this->getJson("/api/v1/sucursales/{$sucursal->id}?include=habitacions", jsonApiHeaders());

    $response->assertOk();
    expect($response->json('included'))->toHaveCount(2)
        ->and(collect($response->json('included'))->pluck('type')->unique()->all())->toBe(['habitacions']);
});

it('lets any user holding sucursales.ver view any sucursal regardless of role', function () {
    $sucursal = Sucursal::factory()->create();
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Passport::actingAs($admin, ['*']);

    $this->getJson("/api/v1/sucursales/{$sucursal->id}", jsonApiHeaders())->assertOk();
});

it('rejects viewing a sucursal for a user without sucursales.ver', function () {
    $sucursal = Sucursal::factory()->create();
    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    Passport::actingAs($supervisor, ['*']);

    $this->getJson("/api/v1/sucursales/{$sucursal->id}", jsonApiHeaders())->assertStatus(403);
});
