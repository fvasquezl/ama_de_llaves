<?php

use App\Models\Habitacion;
use App\Models\Residente;
use App\Models\RondaEnfermeria;
use App\Models\User;
use App\Models\VisitaHabitacion;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('creates a valid visit', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermera->id]);
    $habitacion = Habitacion::factory()->create();
    $residente = Residente::factory()->create();
    Passport::actingAs($enfermera, ['*']);

    $response = $this->postJson('/api/v1/visitas-habitacion', [
        'data' => [
            'type' => 'visitas-habitacion',
            'attributes' => ['hora_programada' => '09:00:00'],
            'relationships' => [
                'rondaEnfermeria' => ['data' => ['type' => 'rondas-enfermeria', 'id' => (string) $ronda->id]],
                'habitacion' => ['data' => ['type' => 'habitacions', 'id' => (string) $habitacion->id]],
                'residente' => ['data' => ['type' => 'residentes', 'id' => (string) $residente->id]],
            ],
        ],
    ], jsonApiHeaders());

    $response->assertCreated();
});

it('rejects a nonexistent habitacion id', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermera->id]);
    $residente = Residente::factory()->create();
    Passport::actingAs($enfermera, ['*']);

    $response = $this->postJson('/api/v1/visitas-habitacion', [
        'data' => [
            'type' => 'visitas-habitacion',
            'attributes' => ['hora_programada' => '09:00:00'],
            'relationships' => [
                'rondaEnfermeria' => ['data' => ['type' => 'rondas-enfermeria', 'id' => (string) $ronda->id]],
                'habitacion' => ['data' => ['type' => 'habitacions', 'id' => '999999']],
                'residente' => ['data' => ['type' => 'residentes', 'id' => (string) $residente->id]],
            ],
        ],
    ], jsonApiHeaders());

    // See RondaEnfermeriaValidationTest for why a nonexistent relationship
    // identifier surfaces as 404 rather than 422 in this package.
    $response->assertStatus(404);
});

it('rejects creating a visit under another enfermeras round', function () {
    $enfermeraA = User::factory()->create();
    $enfermeraA->assignRole('enfermera');
    $enfermeraB = User::factory()->create();
    $enfermeraB->assignRole('enfermera');
    $rondaB = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermeraB->id]);
    $habitacion = Habitacion::factory()->create();
    $residente = Residente::factory()->create();

    Passport::actingAs($enfermeraA, ['*']);

    $response = $this->postJson('/api/v1/visitas-habitacion', [
        'data' => [
            'type' => 'visitas-habitacion',
            'attributes' => ['hora_programada' => '09:00:00'],
            'relationships' => [
                'rondaEnfermeria' => ['data' => ['type' => 'rondas-enfermeria', 'id' => (string) $rondaB->id]],
                'habitacion' => ['data' => ['type' => 'habitacions', 'id' => (string) $habitacion->id]],
                'residente' => ['data' => ['type' => 'residentes', 'id' => (string) $residente->id]],
            ],
        ],
    ], jsonApiHeaders());

    $response->assertStatus(422);
    expect(VisitaHabitacion::count())->toBe(0);
});

it('allows a supervisor to create a visit under any enfermeras round', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermera->id]);
    $habitacion = Habitacion::factory()->create();
    $residente = Residente::factory()->create();

    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    Passport::actingAs($supervisor, ['*']);

    $response = $this->postJson('/api/v1/visitas-habitacion', [
        'data' => [
            'type' => 'visitas-habitacion',
            'attributes' => ['hora_programada' => '09:00:00'],
            'relationships' => [
                'rondaEnfermeria' => ['data' => ['type' => 'rondas-enfermeria', 'id' => (string) $ronda->id]],
                'habitacion' => ['data' => ['type' => 'habitacions', 'id' => (string) $habitacion->id]],
                'residente' => ['data' => ['type' => 'residentes', 'id' => (string) $residente->id]],
            ],
        ],
    ], jsonApiHeaders());

    $response->assertCreated();
});

it('auto stamps nfc_escaneado_at when nfc_verificado is marked true without supplying it', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermera->id]);
    $visita = VisitaHabitacion::factory()->create([
        'ronda_enfermeria_id' => $ronda->id,
        'nfc_verificado' => false,
        'nfc_escaneado_at' => null,
    ]);
    Passport::actingAs($enfermera, ['*']);

    $response = $this->patchJson("/api/v1/visitas-habitacion/{$visita->id}", [
        'data' => [
            'type' => 'visitas-habitacion',
            'id' => (string) $visita->id,
            'attributes' => ['nfc_verificado' => true],
        ],
    ], jsonApiHeaders());

    $response->assertOk();
    expect($visita->refresh()->nfc_verificado)->toBeTrue()
        ->and($visita->nfc_escaneado_at)->not->toBeNull();
});

it('rejects reverting nfc_verificado to false while still supplying nfc_escaneado_at', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermera->id]);
    $visita = VisitaHabitacion::factory()->create([
        'ronda_enfermeria_id' => $ronda->id,
        'nfc_verificado' => true,
        'nfc_escaneado_at' => now(),
    ]);
    Passport::actingAs($enfermera, ['*']);

    $response = $this->patchJson("/api/v1/visitas-habitacion/{$visita->id}", [
        'data' => [
            'type' => 'visitas-habitacion',
            'id' => (string) $visita->id,
            'attributes' => [
                'nfc_verificado' => false,
                'nfc_escaneado_at' => now()->toIso8601String(),
            ],
        ],
    ], jsonApiHeaders());

    $response->assertStatus(422);
    expect($response->json('errors.0.source.pointer'))->toContain('nfc_escaneado_at');
});
