<?php

use App\Models\ChecklistEnfermeriaItem;
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

it('creates a valid checklist item', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermera->id]);
    $visita = VisitaHabitacion::factory()->create(['ronda_enfermeria_id' => $ronda->id]);
    Passport::actingAs($enfermera, ['*']);

    $response = $this->postJson('/api/v1/checklist-enfermeria', [
        'data' => [
            'type' => 'checklist-enfermeria',
            'attributes' => ['descripcion' => 'Verificar signos vitales'],
            'relationships' => [
                'visitaHabitacion' => ['data' => ['type' => 'visitas-habitacion', 'id' => (string) $visita->id]],
            ],
        ],
    ], jsonApiHeaders());

    $response->assertCreated();
});

it('rejects a missing descripcion', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermera->id]);
    $visita = VisitaHabitacion::factory()->create(['ronda_enfermeria_id' => $ronda->id]);
    Passport::actingAs($enfermera, ['*']);

    $response = $this->postJson('/api/v1/checklist-enfermeria', [
        'data' => [
            'type' => 'checklist-enfermeria',
            'relationships' => [
                'visitaHabitacion' => ['data' => ['type' => 'visitas-habitacion', 'id' => (string) $visita->id]],
            ],
        ],
    ], jsonApiHeaders());

    $response->assertStatus(422);
    expect($response->json('errors.0.source.pointer'))->toContain('descripcion');
});

it('rejects a negative orden', function () {
    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermera->id]);
    $visita = VisitaHabitacion::factory()->create(['ronda_enfermeria_id' => $ronda->id]);
    Passport::actingAs($enfermera, ['*']);

    $response = $this->postJson('/api/v1/checklist-enfermeria', [
        'data' => [
            'type' => 'checklist-enfermeria',
            'attributes' => ['descripcion' => 'Item', 'orden' => -1],
            'relationships' => [
                'visitaHabitacion' => ['data' => ['type' => 'visitas-habitacion', 'id' => (string) $visita->id]],
            ],
        ],
    ], jsonApiHeaders());

    $response->assertStatus(422);
    expect($response->json('errors.0.source.pointer'))->toContain('orden');
});

it('rejects adding an item to a visit under another enfermeras round', function () {
    $enfermeraA = User::factory()->create();
    $enfermeraA->assignRole('enfermera');
    $enfermeraB = User::factory()->create();
    $enfermeraB->assignRole('enfermera');
    $rondaB = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermeraB->id]);
    $visitaB = VisitaHabitacion::factory()->create(['ronda_enfermeria_id' => $rondaB->id]);

    Passport::actingAs($enfermeraA, ['*']);

    $response = $this->postJson('/api/v1/checklist-enfermeria', [
        'data' => [
            'type' => 'checklist-enfermeria',
            'attributes' => ['descripcion' => 'Item'],
            'relationships' => [
                'visitaHabitacion' => ['data' => ['type' => 'visitas-habitacion', 'id' => (string) $visitaB->id]],
            ],
        ],
    ], jsonApiHeaders());

    $response->assertStatus(422);
    expect(ChecklistEnfermeriaItem::count())->toBe(0);
});
