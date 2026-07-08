<?php

use App\Models\Inspeccion;
use App\Models\TareaLimpieza;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

/*
 * Phase 8 — spec-part-15/16 coverage for the immutable, ownership-scoped
 * `inspecciones` resource. Owner FK is `supervisora_id` (direct). No
 * `update` or `destroy` route is registered — the legacy controller never
 * had either action.
 *
 * Only `supervisor`/`admin` hold `inspecciones.crear` (Phase 10 seeder
 * gap-fix grants it to `admin` for parity with `supervisor`), and both are
 * themselves in the elevated-bypass role set, so the non-elevated ownership
 * branch is "structurally consistent but currently inert" (spec-part-16) —
 * exercised here via direct `givePermissionTo()` grants on a plain user,
 * mirroring `InspeccionPolicyTest`. `supervisor` is used here for
 * elevated-role coverage since it already holds the full permission set.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('lists only inspections owned by a non-elevated permission holder', function () {
    $usuarioA = User::factory()->create();
    $usuarioA->givePermissionTo('inspecciones.ver');
    $usuarioB = User::factory()->create();
    $usuarioB->givePermissionTo('inspecciones.ver');

    Inspeccion::factory()->count(2)->create(['supervisora_id' => $usuarioA->id]);
    Inspeccion::factory()->count(3)->create(['supervisora_id' => $usuarioB->id]);
    Passport::actingAs($usuarioA, ['*']);

    $response = $this->getJson('/api/v1/inspecciones', jsonApiHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});

it('lets a supervisor list inspections from every owner', function () {
    $usuarioA = User::factory()->create();
    $usuarioA->givePermissionTo('inspecciones.ver');
    $usuarioB = User::factory()->create();
    $usuarioB->givePermissionTo('inspecciones.ver');

    Inspeccion::factory()->count(2)->create(['supervisora_id' => $usuarioA->id]);
    Inspeccion::factory()->count(3)->create(['supervisora_id' => $usuarioB->id]);

    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    Passport::actingAs($supervisor, ['*']);

    $this->getJson('/api/v1/inspecciones', jsonApiHeaders())
        ->assertOk()
        ->assertJsonCount(5, 'data');
});

it('rejects viewing another owners inspection with 403 over real http', function () {
    $dueño = User::factory()->create();
    $inspeccion = Inspeccion::factory()->create(['supervisora_id' => $dueño->id]);

    $otro = User::factory()->create();
    $otro->givePermissionTo('inspecciones.ver');
    Passport::actingAs($otro, ['*']);

    $this->getJson("/api/v1/inspecciones/{$inspeccion->id}", jsonApiHeaders())
        ->assertStatus(403);
});

it('lets a supervisor view any inspection regardless of owner', function () {
    $dueño = User::factory()->create();
    $inspeccion = Inspeccion::factory()->create(['supervisora_id' => $dueño->id]);

    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    Passport::actingAs($supervisor, ['*']);

    $this->getJson("/api/v1/inspecciones/{$inspeccion->id}", jsonApiHeaders())->assertOk();
});

it('includes tareaLimpieza and supervisora via the include query parameter over real http', function () {
    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    $tarea = TareaLimpieza::factory()->create();
    $inspeccion = Inspeccion::factory()->create([
        'tarea_limpieza_id' => $tarea->id,
        'supervisora_id' => $supervisor->id,
    ]);
    Passport::actingAs($supervisor, ['*']);

    $response = $this->getJson("/api/v1/inspecciones/{$inspeccion->id}?include=tareaLimpieza,supervisora", jsonApiHeaders());

    $response->assertOk();
    $types = collect($response->json('included'))->pluck('type');
    expect($types)->toContain('tareas-limpieza')
        ->and($types)->toContain('users');
});

it('lets a supervisor create an inspection', function () {
    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    $tarea = TareaLimpieza::factory()->create();
    Passport::actingAs($supervisor, ['*']);

    $response = $this->postJson('/api/v1/inspecciones', [
        'data' => [
            'type' => 'inspecciones',
            'attributes' => ['resultado' => 'aprobada', 'puntaje' => 95],
            'relationships' => [
                'tareaLimpieza' => ['data' => ['type' => 'tareas-limpieza', 'id' => (string) $tarea->id]],
                'supervisora' => ['data' => ['type' => 'users', 'id' => (string) $supervisor->id]],
            ],
        ],
    ], jsonApiHeaders());

    $response->assertCreated();
    $this->assertDatabaseHas('inspeccions', [
        'tarea_limpieza_id' => $tarea->id,
        'supervisora_id' => $supervisor->id,
        'resultado' => 'aprobada',
    ]);
});

it('lets an admin create an inspection (spec-part-16 gap fix, Phase 10 seeder)', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $tarea = TareaLimpieza::factory()->create();
    Passport::actingAs($admin, ['*']);

    $response = $this->postJson('/api/v1/inspecciones', [
        'data' => [
            'type' => 'inspecciones',
            'attributes' => ['resultado' => 'aprobada', 'puntaje' => 88],
            'relationships' => [
                'tareaLimpieza' => ['data' => ['type' => 'tareas-limpieza', 'id' => (string) $tarea->id]],
                'supervisora' => ['data' => ['type' => 'users', 'id' => (string) $admin->id]],
            ],
        ],
    ], jsonApiHeaders());

    $response->assertCreated();
    $this->assertDatabaseHas('inspeccions', [
        'tarea_limpieza_id' => $tarea->id,
        'supervisora_id' => $admin->id,
        'resultado' => 'aprobada',
    ]);
});

it('rejects a camarera creating an inspection (lacks inspecciones.crear)', function () {
    $camarera = User::factory()->create();
    $camarera->assignRole('camarera');
    $tarea = TareaLimpieza::factory()->create();
    Passport::actingAs($camarera, ['*']);

    $response = $this->postJson('/api/v1/inspecciones', [
        'data' => [
            'type' => 'inspecciones',
            'attributes' => ['resultado' => 'aprobada'],
            'relationships' => [
                'tareaLimpieza' => ['data' => ['type' => 'tareas-limpieza', 'id' => (string) $tarea->id]],
                'supervisora' => ['data' => ['type' => 'users', 'id' => (string) $camarera->id]],
            ],
        ],
    ], jsonApiHeaders());

    $response->assertStatus(403);
});

it('rejects an invalid resultado with a 422', function () {
    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    $tarea = TareaLimpieza::factory()->create();
    Passport::actingAs($supervisor, ['*']);

    $response = $this->postJson('/api/v1/inspecciones', [
        'data' => [
            'type' => 'inspecciones',
            'attributes' => ['resultado' => 'pendiente'],
            'relationships' => [
                'tareaLimpieza' => ['data' => ['type' => 'tareas-limpieza', 'id' => (string) $tarea->id]],
                'supervisora' => ['data' => ['type' => 'users', 'id' => (string) $supervisor->id]],
            ],
        ],
    ], jsonApiHeaders());

    $response->assertStatus(422);
    expect($response->json('errors.0.source.pointer'))->toContain('resultado');
});

it('filters inspecciones by resultado over real http', function () {
    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    Inspeccion::factory()->create(['resultado' => 'aprobada']);
    Inspeccion::factory()->create(['resultado' => 'rechazada']);
    Passport::actingAs($supervisor, ['*']);

    $response = $this->getJson('/api/v1/inspecciones?filter[resultado]=aprobada', jsonApiHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.attributes.resultado'))->toBe('aprobada');
});

it('returns 405 for an update attempt on inspecciones — no update route is registered (spec-part-15)', function () {
    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    $inspeccion = Inspeccion::factory()->create(['supervisora_id' => $supervisor->id]);
    Passport::actingAs($supervisor, ['*']);

    // The id-scoped path IS registered (for GET, i.e. `show`), so Laravel's
    // router raises MethodNotAllowedHttpException (405) rather than a bare
    // "route not found" 404 — same documented deviation as
    // RouteExposureTest / TareaLimpiezaTest / ChecklistItemTest. The
    // spec's literal wording of "404" doesn't hold given how Laravel's
    // router actually behaves; the underlying intent ("no update route is
    // registered", i.e. the resource is immutable) is fully satisfied.
    $this->patchJson("/api/v1/inspecciones/{$inspeccion->id}", [
        'data' => ['type' => 'inspecciones', 'id' => (string) $inspeccion->id],
    ], jsonApiHeaders())->assertStatus(405);
});

it('returns 405 for DELETE on inspecciones — no destroy route is registered', function () {
    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    $inspeccion = Inspeccion::factory()->create(['supervisora_id' => $supervisor->id]);
    Passport::actingAs($supervisor, ['*']);

    $this->deleteJson("/api/v1/inspecciones/{$inspeccion->id}", [], jsonApiHeaders())
        ->assertStatus(405);
});
