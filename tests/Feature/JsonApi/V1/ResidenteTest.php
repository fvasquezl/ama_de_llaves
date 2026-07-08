<?php

use App\Models\Estancia;
use App\Models\Residente;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

/*
 * Phase 5 — spec-part-06/07/08 coverage for the flat/permission-only
 * `residentes` resource, expanded in place from the minimal, route-less
 * schema originally built for enfermeria-api. Exercised over real HTTP
 * through the newly mounted routes.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('lets a user with residentes.crear create a residente', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Passport::actingAs($admin, ['*']);

    $response = $this->postJson('/api/v1/residentes', [
        'data' => [
            'type' => 'residentes',
            'attributes' => [
                'nombre' => 'Juana',
                'apellidos' => 'Perez',
                'fecha_nacimiento' => '1950-01-01',
                'curp' => 'PEXJ500101MDFRRN01',
                'contacto_emergencia' => 'Hijo: Luis Perez',
                'telefono_emergencia' => '555-0100',
            ],
        ],
    ], jsonApiHeaders());

    $response->assertCreated();
    $this->assertDatabaseHas('residentes', ['nombre' => 'Juana', 'apellidos' => 'Perez']);
});

it('rejects an enfermera creating a residente (has ver, lacks crear)', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    Passport::actingAs($enfermera, ['*']);

    $response = $this->postJson('/api/v1/residentes', [
        'data' => [
            'type' => 'residentes',
            'attributes' => [
                'nombre' => 'Roberto',
                'apellidos' => 'Gomez',
                'fecha_nacimiento' => '1945-05-05',
                'contacto_emergencia' => 'Hija: Ana Gomez',
                'telefono_emergencia' => '555-0200',
            ],
        ],
    ], jsonApiHeaders());

    $response->assertStatus(403);
    $this->assertDatabaseMissing('residentes', ['nombre' => 'Roberto']);
});

it('lets an enfermera view but not create residentes (spec-part-08 scenario)', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    Passport::actingAs($enfermera, ['*']);

    $this->getJson('/api/v1/residentes', jsonApiHeaders())->assertOk();
    $this->postJson('/api/v1/residentes', [
        'data' => [
            'type' => 'residentes',
            'attributes' => [
                'nombre' => 'X',
                'apellidos' => 'Y',
                'fecha_nacimiento' => '1940-01-01',
                'contacto_emergencia' => 'Z',
                'telefono_emergencia' => '555-0000',
            ],
        ],
    ], jsonApiHeaders())->assertStatus(403);
});

it('lets a user with residentes.editar update a residentes diagnostico', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $residente = Residente::factory()->create(['diagnostico' => 'Ninguno']);
    Passport::actingAs($admin, ['*']);

    $response = $this->patchJson("/api/v1/residentes/{$residente->id}", [
        'data' => [
            'type' => 'residentes',
            'id' => (string) $residente->id,
            'attributes' => ['diagnostico' => 'Hipertension'],
        ],
    ], jsonApiHeaders());

    $response->assertOk();
    expect($residente->refresh()->diagnostico)->toBe('Hipertension');
});

it('soft-deletes a residente on destroy instead of hard-deleting it', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $residente = Residente::factory()->create();
    Passport::actingAs($admin, ['*']);

    $response = $this->deleteJson("/api/v1/residentes/{$residente->id}", [], jsonApiHeaders());

    $response->assertStatus(204);
    $this->assertSoftDeleted('residentes', ['id' => $residente->id]);
});

it('rejects a missing contacto_emergencia with a 422', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Passport::actingAs($admin, ['*']);

    $response = $this->postJson('/api/v1/residentes', [
        'data' => [
            'type' => 'residentes',
            'attributes' => [
                'nombre' => 'Sin',
                'apellidos' => 'Contacto',
                'fecha_nacimiento' => '1960-01-01',
                'telefono_emergencia' => '555-0300',
            ],
        ],
    ], jsonApiHeaders());

    $response->assertStatus(422);
    $response->assertJsonFragment(['source' => ['pointer' => '/data/attributes/contacto_emergencia']]);
});

it('rejects a duplicate curp with a 422', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Residente::factory()->create(['curp' => 'DUPX600101MDFRRN02']);
    Passport::actingAs($admin, ['*']);

    $response = $this->postJson('/api/v1/residentes', [
        'data' => [
            'type' => 'residentes',
            'attributes' => [
                'nombre' => 'Otro',
                'apellidos' => 'Residente',
                'fecha_nacimiento' => '1960-01-01',
                'curp' => 'DUPX600101MDFRRN02',
                'contacto_emergencia' => 'Hermano: Pedro',
                'telefono_emergencia' => '555-0400',
            ],
        ],
    ], jsonApiHeaders());

    $response->assertStatus(422);
    $response->assertJsonFragment(['source' => ['pointer' => '/data/attributes/curp']]);
});

it('exposes all pii fields to a user with residentes.ver (no field-level restriction)', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $residente = Residente::factory()->create([
        'curp' => 'PIIX700101MDFRRN03',
        'diagnostico' => 'Diabetes',
        'alergias' => 'Penicilina',
        'contacto_emergencia' => 'Esposa: Rosa',
        'telefono_emergencia' => '555-0500',
    ]);
    Passport::actingAs($enfermera, ['*']);

    $response = $this->getJson("/api/v1/residentes/{$residente->id}", jsonApiHeaders());

    $response->assertOk();
    expect($response->json('data.attributes.curp'))->toBe('PIIX700101MDFRRN03')
        ->and($response->json('data.attributes.diagnostico'))->toBe('Diabetes')
        ->and($response->json('data.attributes.alergias'))->toBe('Penicilina')
        ->and($response->json('data.attributes.contacto_emergencia'))->toBe('Esposa: Rosa')
        ->and($response->json('data.attributes.telefono_emergencia'))->toBe('555-0500');
});

it('includes a residentes estancias via the include query parameter (Phase 6 forward-declaration closed)', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $residente = Residente::factory()->create();
    Estancia::factory()->create(['residente_id' => $residente->id]);
    Passport::actingAs($admin, ['*']);

    $response = $this->getJson("/api/v1/residentes/{$residente->id}?include=estancias", jsonApiHeaders());

    $response->assertOk();
    expect($response->json('included'))->toHaveCount(1)
        ->and($response->json('included.0.type'))->toBe('estancias');
});

it('filters residentes by nombre', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Residente::factory()->create(['nombre' => 'Carlos']);
    Residente::factory()->create(['nombre' => 'Beatriz']);
    Passport::actingAs($admin, ['*']);

    $response = $this->getJson('/api/v1/residentes?filter[nombre]=Carlos', jsonApiHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.attributes.nombre'))->toBe('Carlos');
});
