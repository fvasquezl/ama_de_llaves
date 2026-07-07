<?php

use App\Models\ReporteEnfermeria;
use App\Models\RondaEnfermeria;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('creates a report in borrador state', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermera->id]);
    Passport::actingAs($enfermera, ['*']);

    $response = $this->postJson('/api/v1/reportes-enfermeria', [
        'data' => [
            'type' => 'reportes-enfermeria',
            'attributes' => ['observaciones' => 'Turno sin novedades'],
            'relationships' => [
                'rondaEnfermeria' => ['data' => ['type' => 'rondas-enfermeria', 'id' => (string) $ronda->id]],
                'enfermera' => ['data' => ['type' => 'users', 'id' => (string) $enfermera->id]],
            ],
        ],
    ], jsonApiHeaders());

    $response->assertCreated();
    expect($response->json('data.attributes.estado'))->toBe('borrador');
});

it('rejects creating a report already signed', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermera->id]);
    Passport::actingAs($enfermera, ['*']);

    $response = $this->postJson('/api/v1/reportes-enfermeria', [
        'data' => [
            'type' => 'reportes-enfermeria',
            'attributes' => ['estado' => 'firmado'],
            'relationships' => [
                'rondaEnfermeria' => ['data' => ['type' => 'rondas-enfermeria', 'id' => (string) $ronda->id]],
                'enfermera' => ['data' => ['type' => 'users', 'id' => (string) $enfermera->id]],
            ],
        ],
    ], jsonApiHeaders());

    $response->assertStatus(422);
    expect($response->json('errors.0.source.pointer'))->toContain('estado');
});

it('rejects a duplicate report for the same round', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermera->id]);
    ReporteEnfermeria::factory()->create(['ronda_enfermeria_id' => $ronda->id, 'enfermera_id' => $enfermera->id, 'estado' => 'borrador']);
    Passport::actingAs($enfermera, ['*']);

    $response = $this->postJson('/api/v1/reportes-enfermeria', [
        'data' => [
            'type' => 'reportes-enfermeria',
            'relationships' => [
                'rondaEnfermeria' => ['data' => ['type' => 'rondas-enfermeria', 'id' => (string) $ronda->id]],
                'enfermera' => ['data' => ['type' => 'users', 'id' => (string) $enfermera->id]],
            ],
        ],
    ], jsonApiHeaders());

    $response->assertStatus(422);
    expect($response->json('errors.0.source.pointer'))->toContain('rondaEnfermeria');
});

it('signing a report stamps the signature time', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermera->id]);
    $reporte = ReporteEnfermeria::factory()->create([
        'ronda_enfermeria_id' => $ronda->id,
        'enfermera_id' => $enfermera->id,
        'estado' => 'borrador',
        'firmado_at' => null,
    ]);
    Passport::actingAs($enfermera, ['*']);

    $response = $this->patchJson("/api/v1/reportes-enfermeria/{$reporte->id}", [
        'data' => [
            'type' => 'reportes-enfermeria',
            'id' => (string) $reporte->id,
            'attributes' => ['estado' => 'firmado'],
        ],
    ], jsonApiHeaders());

    $response->assertOk();
    $reporte->refresh();
    expect($reporte->estado)->toBe('firmado')
        ->and($reporte->firmado_at)->not->toBeNull();
});

it('returns exactly 409 for any update attempt on a signed report', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermera->id]);
    $reporte = ReporteEnfermeria::factory()->create([
        'ronda_enfermeria_id' => $ronda->id,
        'enfermera_id' => $enfermera->id,
        'estado' => 'firmado',
        'firmado_at' => now(),
        'observaciones' => 'original',
    ]);
    Passport::actingAs($enfermera, ['*']);

    $response = $this->patchJson("/api/v1/reportes-enfermeria/{$reporte->id}", [
        'data' => [
            'type' => 'reportes-enfermeria',
            'id' => (string) $reporte->id,
            'attributes' => ['observaciones' => 'intento de cambio'],
        ],
    ], jsonApiHeaders());

    $response->assertStatus(409);
    expect($reporte->refresh()->observaciones)->toBe('original');
});

it('returns 409 for an attempt to revert a signed report back to borrador', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermera->id]);
    $reporte = ReporteEnfermeria::factory()->create([
        'ronda_enfermeria_id' => $ronda->id,
        'enfermera_id' => $enfermera->id,
        'estado' => 'firmado',
        'firmado_at' => now(),
    ]);
    Passport::actingAs($enfermera, ['*']);

    $response = $this->patchJson("/api/v1/reportes-enfermeria/{$reporte->id}", [
        'data' => [
            'type' => 'reportes-enfermeria',
            'id' => (string) $reporte->id,
            'attributes' => ['estado' => 'borrador'],
        ],
    ], jsonApiHeaders());

    $response->assertStatus(409);
    expect($reporte->refresh()->estado)->toBe('firmado');
});
