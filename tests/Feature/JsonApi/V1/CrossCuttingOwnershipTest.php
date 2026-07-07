<?php

use App\Models\AlertaRonda;
use App\Models\ChecklistEnfermeriaItem;
use App\Models\ReporteEnfermeria;
use App\Models\RondaEnfermeria;
use App\Models\User;
use App\Models\VisitaHabitacion;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

/*
 * Phase 8.6 — cross-cutting authorization model (spec-part-17/18), driven
 * over real HTTP through the mounted routes for all 5 resources at once.
 * Builds one full ownership chain — round -> visit -> checklist item ->
 * report -> alert, all ultimately tied to enfermera A — and asserts a
 * second enfermera (B) is independently denied on every one of the 5
 * record types, then asserts an elevated role (admin) bypasses that
 * scoping entirely and can mutate a record it does not own.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * @return array{ronda: RondaEnfermeria, visita: VisitaHabitacion, item: ChecklistEnfermeriaItem, reporte: ReporteEnfermeria, alerta: AlertaRonda}
 */
function buildOwnershipChainFor(User $enfermera): array
{
    $ronda = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermera->id]);
    $visita = VisitaHabitacion::factory()->create(['ronda_enfermeria_id' => $ronda->id]);
    $item = ChecklistEnfermeriaItem::factory()->create(['visita_habitacion_id' => $visita->id]);
    $reporte = ReporteEnfermeria::factory()->create([
        'ronda_enfermeria_id' => $ronda->id,
        'enfermera_id' => $enfermera->id,
        'estado' => 'borrador',
    ]);
    $alerta = AlertaRonda::factory()->create([
        'ronda_enfermeria_id' => $ronda->id,
        'visita_habitacion_id' => $visita->id,
        'atendido' => false,
        'atendido_por_id' => null,
    ]);

    return compact('ronda', 'visita', 'item', 'reporte', 'alerta');
}

it('denies enfermera B independent GET and PATCH access to every record in enfermera As chain', function () {
    $enfermeraA = User::factory()->create();
    $enfermeraA->assignRole('enfermera');
    $enfermeraB = User::factory()->create();
    $enfermeraB->assignRole('enfermera');

    $chain = buildOwnershipChainFor($enfermeraA);
    Passport::actingAs($enfermeraB, ['*']);

    $endpoints = [
        'rondas-enfermeria' => $chain['ronda'],
        'visitas-habitacion' => $chain['visita'],
        'checklist-enfermeria' => $chain['item'],
        'reportes-enfermeria' => $chain['reporte'],
        'alertas-ronda' => $chain['alerta'],
    ];

    foreach ($endpoints as $type => $record) {
        $this->getJson("/api/v1/{$type}/{$record->id}", jsonApiHeaders())
            ->assertStatus(403);
    }

    // Attempt a harmless no-op PATCH per resource; ownership denial must
    // fire before any field-level validation, independent of payload.
    $this->patchJson("/api/v1/rondas-enfermeria/{$chain['ronda']->id}", [
        'data' => ['type' => 'rondas-enfermeria', 'id' => (string) $chain['ronda']->id, 'attributes' => ['notas' => 'x']],
    ], jsonApiHeaders())->assertStatus(403);

    $this->patchJson("/api/v1/visitas-habitacion/{$chain['visita']->id}", [
        'data' => ['type' => 'visitas-habitacion', 'id' => (string) $chain['visita']->id, 'attributes' => ['notas' => 'x']],
    ], jsonApiHeaders())->assertStatus(403);

    $this->patchJson("/api/v1/checklist-enfermeria/{$chain['item']->id}", [
        'data' => ['type' => 'checklist-enfermeria', 'id' => (string) $chain['item']->id, 'attributes' => ['completado' => true]],
    ], jsonApiHeaders())->assertStatus(403);

    $this->patchJson("/api/v1/reportes-enfermeria/{$chain['reporte']->id}", [
        'data' => ['type' => 'reportes-enfermeria', 'id' => (string) $chain['reporte']->id, 'attributes' => ['observaciones' => 'x']],
    ], jsonApiHeaders())->assertStatus(403);

    $this->patchJson("/api/v1/alertas-ronda/{$chain['alerta']->id}", [
        'data' => ['type' => 'alertas-ronda', 'id' => (string) $chain['alerta']->id, 'attributes' => ['atendido' => true]],
    ], jsonApiHeaders())->assertStatus(403);
});

it('lets an admin PATCH a round owned by another enfermera and receive 200', function () {
    $enfermeraA = User::factory()->create();
    $enfermeraA->assignRole('enfermera');
    $chain = buildOwnershipChainFor($enfermeraA);

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Passport::actingAs($admin, ['*']);

    $response = $this->patchJson("/api/v1/rondas-enfermeria/{$chain['ronda']->id}", [
        'data' => [
            'type' => 'rondas-enfermeria',
            'id' => (string) $chain['ronda']->id,
            'attributes' => ['notas' => 'editado por admin'],
        ],
    ], jsonApiHeaders());

    $response->assertOk();
    expect($chain['ronda']->refresh()->notas)->toBe('editado por admin');
});

it('lets an admin bypass ownership scoping and GET every record in another enfermeras chain', function () {
    $enfermeraA = User::factory()->create();
    $enfermeraA->assignRole('enfermera');
    $chain = buildOwnershipChainFor($enfermeraA);

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Passport::actingAs($admin, ['*']);

    $endpoints = [
        'rondas-enfermeria' => $chain['ronda'],
        'visitas-habitacion' => $chain['visita'],
        'checklist-enfermeria' => $chain['item'],
        'reportes-enfermeria' => $chain['reporte'],
        'alertas-ronda' => $chain['alerta'],
    ];

    foreach ($endpoints as $type => $record) {
        $this->getJson("/api/v1/{$type}/{$record->id}", jsonApiHeaders())
            ->assertOk();
    }
});
