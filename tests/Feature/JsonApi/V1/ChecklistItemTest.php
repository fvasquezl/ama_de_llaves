<?php

use App\Models\ChecklistItem;
use App\Models\TareaLimpieza;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

/*
 * Phase 8 — spec-part-12/13/14 coverage for the two-hop-ownership-scoped
 * `checklist-limpieza` resource. Owner is resolved via
 * `tareaLimpieza.camarera_id` (the item itself has no owner FK).
 *
 * The seeded `camarera` role holds `checklist-limpieza.ver`/`.editar` but
 * NOT `.crear` (mirrors `tareas-limpieza.crear` being withheld from
 * camarera too) — so the create/cross-tenant-validation tests below grant
 * `checklist-limpieza.crear` directly to a plain (non-elevated) user via
 * `givePermissionTo()` rather than relying on a seeded role, to exercise
 * `ChecklistItemRequest::assertOwnsParentTarea()` in isolation, deliberately
 * bypassing the elevated `admin`/`supervisor` roles (both hold `.crear` as
 * of the Phase 10 seeder gap-fix and would skip the cross-tenant check) —
 * `supervisor` is used here for elevated-bypass coverage since it already
 * holds the full permission set.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('lists only checklist items whose two-hop chain resolves to the authenticated camarera', function () {
    $camareraA = User::factory()->create();
    $camareraA->assignRole('camarera');
    $camareraB = User::factory()->create();
    $camareraB->assignRole('camarera');
    $tareaA = TareaLimpieza::factory()->create(['camarera_id' => $camareraA->id]);
    $tareaB = TareaLimpieza::factory()->create(['camarera_id' => $camareraB->id]);

    ChecklistItem::factory()->count(2)->create(['tarea_limpieza_id' => $tareaA->id]);
    ChecklistItem::factory()->count(3)->create(['tarea_limpieza_id' => $tareaB->id]);
    Passport::actingAs($camareraA, ['*']);

    $response = $this->getJson('/api/v1/checklist-limpieza', jsonApiHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});

it('lets a supervisor list checklist items from every camarera', function () {
    $camareraA = User::factory()->create();
    $camareraA->assignRole('camarera');
    $camareraB = User::factory()->create();
    $camareraB->assignRole('camarera');
    $tareaA = TareaLimpieza::factory()->create(['camarera_id' => $camareraA->id]);
    $tareaB = TareaLimpieza::factory()->create(['camarera_id' => $camareraB->id]);

    ChecklistItem::factory()->count(2)->create(['tarea_limpieza_id' => $tareaA->id]);
    ChecklistItem::factory()->count(3)->create(['tarea_limpieza_id' => $tareaB->id]);

    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    Passport::actingAs($supervisor, ['*']);

    $this->getJson('/api/v1/checklist-limpieza', jsonApiHeaders())
        ->assertOk()
        ->assertJsonCount(5, 'data');
});

it('rejects viewing a checklist item under another camareras chain with 403 over real http', function () {
    $camareraA = User::factory()->create();
    $camareraA->assignRole('camarera');
    $camareraB = User::factory()->create();
    $camareraB->assignRole('camarera');
    $tareaB = TareaLimpieza::factory()->create(['camarera_id' => $camareraB->id]);
    $item = ChecklistItem::factory()->create(['tarea_limpieza_id' => $tareaB->id]);
    Passport::actingAs($camareraA, ['*']);

    $this->getJson("/api/v1/checklist-limpieza/{$item->id}", jsonApiHeaders())
        ->assertStatus(403);
});

it('rejects updating a checklist item under another camareras chain with 403 over real http', function () {
    $camareraA = User::factory()->create();
    $camareraA->assignRole('camarera');
    $camareraB = User::factory()->create();
    $camareraB->assignRole('camarera');
    $tareaB = TareaLimpieza::factory()->create(['camarera_id' => $camareraB->id]);
    $item = ChecklistItem::factory()->create(['tarea_limpieza_id' => $tareaB->id, 'completado' => false]);
    Passport::actingAs($camareraA, ['*']);

    $response = $this->patchJson("/api/v1/checklist-limpieza/{$item->id}", [
        'data' => [
            'type' => 'checklist-limpieza',
            'id' => (string) $item->id,
            'attributes' => ['completado' => true],
        ],
    ], jsonApiHeaders());

    $response->assertStatus(403);
    expect($item->refresh()->completado)->toBeFalse();
});

it('lets a camarera update her own checklist item', function () {
    $camarera = User::factory()->create();
    $camarera->assignRole('camarera');
    $tarea = TareaLimpieza::factory()->create(['camarera_id' => $camarera->id]);
    $item = ChecklistItem::factory()->create(['tarea_limpieza_id' => $tarea->id, 'completado' => false]);
    Passport::actingAs($camarera, ['*']);

    $response = $this->patchJson("/api/v1/checklist-limpieza/{$item->id}", [
        'data' => [
            'type' => 'checklist-limpieza',
            'id' => (string) $item->id,
            'attributes' => ['completado' => true],
        ],
    ], jsonApiHeaders());

    $response->assertOk();
    expect($item->refresh()->completado)->toBeTrue();
});

it('lets a supervisor view and update a checklist item under any camareras chain', function () {
    $camarera = User::factory()->create();
    $camarera->assignRole('camarera');
    $tarea = TareaLimpieza::factory()->create(['camarera_id' => $camarera->id]);
    $item = ChecklistItem::factory()->create(['tarea_limpieza_id' => $tarea->id, 'completado' => false]);

    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    Passport::actingAs($supervisor, ['*']);

    $this->getJson("/api/v1/checklist-limpieza/{$item->id}", jsonApiHeaders())->assertOk();

    $response = $this->patchJson("/api/v1/checklist-limpieza/{$item->id}", [
        'data' => [
            'type' => 'checklist-limpieza',
            'id' => (string) $item->id,
            'attributes' => ['completado' => true],
        ],
    ], jsonApiHeaders());

    $response->assertOk();
    expect($item->refresh()->completado)->toBeTrue();
});

it('includes tareaLimpieza via the include query parameter over real http', function () {
    $camarera = User::factory()->create();
    $camarera->assignRole('camarera');
    $tarea = TareaLimpieza::factory()->create(['camarera_id' => $camarera->id]);
    $item = ChecklistItem::factory()->create(['tarea_limpieza_id' => $tarea->id]);
    Passport::actingAs($camarera, ['*']);

    $response = $this->getJson("/api/v1/checklist-limpieza/{$item->id}?include=tareaLimpieza", jsonApiHeaders());

    $response->assertOk();
    expect($response->json('included.0.type'))->toBe('tareas-limpieza')
        ->and($response->json('included.0.id'))->toBe((string) $tarea->id);
});

it('filters checklist-limpieza by completado over real http', function () {
    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    ChecklistItem::factory()->create(['completado' => true]);
    ChecklistItem::factory()->create(['completado' => false]);
    Passport::actingAs($supervisor, ['*']);

    $response = $this->getJson('/api/v1/checklist-limpieza?filter[completado]=true', jsonApiHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
});

it('lets a non-elevated user with checklist-limpieza.crear create an item under her own task', function () {
    $usuario = User::factory()->create();
    $usuario->givePermissionTo('checklist-limpieza.crear');
    $tarea = TareaLimpieza::factory()->create(['camarera_id' => $usuario->id]);
    Passport::actingAs($usuario, ['*']);

    $response = $this->postJson('/api/v1/checklist-limpieza', [
        'data' => [
            'type' => 'checklist-limpieza',
            'attributes' => ['descripcion' => 'Tender la cama'],
            'relationships' => [
                'tareaLimpieza' => ['data' => ['type' => 'tareas-limpieza', 'id' => (string) $tarea->id]],
            ],
        ],
    ], jsonApiHeaders());

    $response->assertCreated();
    $this->assertDatabaseHas('checklist_items', [
        'tarea_limpieza_id' => $tarea->id,
        'descripcion' => 'Tender la cama',
    ]);
});

it('rejects a non-elevated user with checklist-limpieza.crear adding an item to a task she does not own', function () {
    $dueña = User::factory()->create();
    $tarea = TareaLimpieza::factory()->create(['camarera_id' => $dueña->id]);

    $otra = User::factory()->create();
    $otra->givePermissionTo('checklist-limpieza.crear');
    Passport::actingAs($otra, ['*']);

    $response = $this->postJson('/api/v1/checklist-limpieza', [
        'data' => [
            'type' => 'checklist-limpieza',
            'attributes' => ['descripcion' => 'Item'],
            'relationships' => [
                'tareaLimpieza' => ['data' => ['type' => 'tareas-limpieza', 'id' => (string) $tarea->id]],
            ],
        ],
    ], jsonApiHeaders());

    $response->assertStatus(422);
    expect(ChecklistItem::count())->toBe(0);
});

it('lets a supervisor create a checklist item under any camareras task (elevated bypass)', function () {
    $camarera = User::factory()->create();
    $camarera->assignRole('camarera');
    $tarea = TareaLimpieza::factory()->create(['camarera_id' => $camarera->id]);

    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    Passport::actingAs($supervisor, ['*']);

    $response = $this->postJson('/api/v1/checklist-limpieza', [
        'data' => [
            'type' => 'checklist-limpieza',
            'attributes' => ['descripcion' => 'Revisar minibar'],
            'relationships' => [
                'tareaLimpieza' => ['data' => ['type' => 'tareas-limpieza', 'id' => (string) $tarea->id]],
            ],
        ],
    ], jsonApiHeaders());

    $response->assertCreated();
});

it('lets an admin create and edit a checklist item under any camareras task (spec-part-14 gap fix, Phase 10 seeder)', function () {
    $camarera = User::factory()->create();
    $camarera->assignRole('camarera');
    $tarea = TareaLimpieza::factory()->create(['camarera_id' => $camarera->id]);

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Passport::actingAs($admin, ['*']);

    $createResponse = $this->postJson('/api/v1/checklist-limpieza', [
        'data' => [
            'type' => 'checklist-limpieza',
            'attributes' => ['descripcion' => 'Revisar aire acondicionado'],
            'relationships' => [
                'tareaLimpieza' => ['data' => ['type' => 'tareas-limpieza', 'id' => (string) $tarea->id]],
            ],
        ],
    ], jsonApiHeaders());
    $createResponse->assertCreated();
    $itemId = $createResponse->json('data.id');

    $updateResponse = $this->patchJson("/api/v1/checklist-limpieza/{$itemId}", [
        'data' => [
            'type' => 'checklist-limpieza',
            'id' => $itemId,
            'attributes' => ['completado' => true],
        ],
    ], jsonApiHeaders());
    $updateResponse->assertOk();
});

it('rejects a missing descripcion with a 422', function () {
    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    $tarea = TareaLimpieza::factory()->create();
    Passport::actingAs($supervisor, ['*']);

    $response = $this->postJson('/api/v1/checklist-limpieza', [
        'data' => [
            'type' => 'checklist-limpieza',
            'relationships' => [
                'tareaLimpieza' => ['data' => ['type' => 'tareas-limpieza', 'id' => (string) $tarea->id]],
            ],
        ],
    ], jsonApiHeaders());

    $response->assertStatus(422);
    expect($response->json('errors.0.source.pointer'))->toContain('descripcion');
});

it('rejects a negative orden with a 422', function () {
    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    $tarea = TareaLimpieza::factory()->create();
    Passport::actingAs($supervisor, ['*']);

    $response = $this->postJson('/api/v1/checklist-limpieza', [
        'data' => [
            'type' => 'checklist-limpieza',
            'attributes' => ['descripcion' => 'Item', 'orden' => -1],
            'relationships' => [
                'tareaLimpieza' => ['data' => ['type' => 'tareas-limpieza', 'id' => (string) $tarea->id]],
            ],
        ],
    ], jsonApiHeaders());

    $response->assertStatus(422);
    expect($response->json('errors.0.source.pointer'))->toContain('orden');
});

it('rejects a nonexistent tareaLimpieza', function () {
    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    Passport::actingAs($supervisor, ['*']);

    $response = $this->postJson('/api/v1/checklist-limpieza', [
        'data' => [
            'type' => 'checklist-limpieza',
            'attributes' => ['descripcion' => 'Item'],
            'relationships' => [
                'tareaLimpieza' => ['data' => ['type' => 'tareas-limpieza', 'id' => '999999']],
            ],
        ],
    ], jsonApiHeaders());

    // See VisitaHabitacionValidationTest / RondaEnfermeriaValidationTest for
    // why a nonexistent relationship identifier surfaces as 404 (resolved by
    // the package before the Form Request's `exists` rule ever runs) rather
    // than 422 — same documented deviation, not specific to this domain.
    $response->assertStatus(404);
});

it('returns 405 for DELETE on checklist-limpieza — no destroy route is registered (spec-part-12)', function () {
    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    $item = ChecklistItem::factory()->create();
    Passport::actingAs($supervisor, ['*']);

    // The id-scoped path IS registered (for GET/PATCH), so Laravel's router
    // raises MethodNotAllowedHttpException (405) rather than a bare
    // "route not found" 404 — same documented deviation as
    // RouteExposureTest / TareaLimpiezaTest. The spec's literal wording of
    // "404" doesn't hold given how Laravel's router actually behaves; the
    // underlying intent ("no destroy route is registered") is fully
    // satisfied.
    $this->deleteJson("/api/v1/checklist-limpieza/{$item->id}", [], jsonApiHeaders())
        ->assertStatus(405);
});
