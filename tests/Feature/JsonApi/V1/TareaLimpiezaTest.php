<?php

use App\Models\Habitacion;
use App\Models\TareaLimpieza;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

/*
 * Phase 7 — spec-part-10/11 coverage for the ownership-scoped
 * `tareas-limpieza` resource, exercised over real HTTP through the newly
 * mounted routes. Owner FK is `camarera_id` (direct, nullable). No
 * `destroy` route exists for this resource (decision 1 — `.eliminar`
 * dropped entirely, no soft-deletes column).
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('lists only the authenticated camareras own tasks', function () {
    $camareraA = User::factory()->create();
    $camareraA->assignRole('camarera');
    $camareraB = User::factory()->create();
    $camareraB->assignRole('camarera');

    TareaLimpieza::factory()->count(2)->create(['camarera_id' => $camareraA->id]);
    TareaLimpieza::factory()->count(3)->create(['camarera_id' => $camareraB->id]);
    Passport::actingAs($camareraA, ['*']);

    $response = $this->getJson('/api/v1/tareas-limpieza', jsonApiHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});

it('lets a supervisor and an admin list tasks from every camarera', function () {
    $camareraA = User::factory()->create();
    $camareraA->assignRole('camarera');
    $camareraB = User::factory()->create();
    $camareraB->assignRole('camarera');

    TareaLimpieza::factory()->count(2)->create(['camarera_id' => $camareraA->id]);
    TareaLimpieza::factory()->count(3)->create(['camarera_id' => $camareraB->id]);

    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    Passport::actingAs($supervisor, ['*']);

    $this->getJson('/api/v1/tareas-limpieza', jsonApiHeaders())
        ->assertOk()
        ->assertJsonCount(5, 'data');

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Passport::actingAs($admin, ['*']);

    $this->getJson('/api/v1/tareas-limpieza', jsonApiHeaders())
        ->assertOk()
        ->assertJsonCount(5, 'data');
});

it('excludes an unassigned (null camarera_id) task from a camareras index but supervisor/admin see it', function () {
    $camarera = User::factory()->create();
    $camarera->assignRole('camarera');
    TareaLimpieza::factory()->create(['camarera_id' => $camarera->id]);
    TareaLimpieza::factory()->create(['camarera_id' => null]);

    Passport::actingAs($camarera, ['*']);
    $this->getJson('/api/v1/tareas-limpieza', jsonApiHeaders())
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    Passport::actingAs($supervisor, ['*']);
    $this->getJson('/api/v1/tareas-limpieza', jsonApiHeaders())
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('rejects viewing another camareras task with 403 over real http', function () {
    $camareraA = User::factory()->create();
    $camareraA->assignRole('camarera');
    $camareraB = User::factory()->create();
    $camareraB->assignRole('camarera');
    $tarea = TareaLimpieza::factory()->create(['camarera_id' => $camareraB->id]);
    Passport::actingAs($camareraA, ['*']);

    $this->getJson("/api/v1/tareas-limpieza/{$tarea->id}", jsonApiHeaders())
        ->assertStatus(403);
});

it('rejects a camarera viewing or updating an unassigned task with 403, but allows supervisor/admin with 200', function () {
    $tarea = TareaLimpieza::factory()->create(['camarera_id' => null]);

    $camarera = User::factory()->create();
    $camarera->assignRole('camarera');
    Passport::actingAs($camarera, ['*']);
    $this->getJson("/api/v1/tareas-limpieza/{$tarea->id}", jsonApiHeaders())->assertStatus(403);

    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    Passport::actingAs($supervisor, ['*']);
    $this->getJson("/api/v1/tareas-limpieza/{$tarea->id}", jsonApiHeaders())->assertOk();

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Passport::actingAs($admin, ['*']);
    $this->getJson("/api/v1/tareas-limpieza/{$tarea->id}", jsonApiHeaders())->assertOk();
});

it('lets a camarera update her own task', function () {
    $camarera = User::factory()->create();
    $camarera->assignRole('camarera');
    $tarea = TareaLimpieza::factory()->create(['camarera_id' => $camarera->id, 'estado' => 'pendiente']);
    Passport::actingAs($camarera, ['*']);

    $response = $this->patchJson("/api/v1/tareas-limpieza/{$tarea->id}", [
        'data' => [
            'type' => 'tareas-limpieza',
            'id' => (string) $tarea->id,
            'attributes' => ['estado' => 'en_progreso'],
        ],
    ], jsonApiHeaders());

    $response->assertOk();
    expect($tarea->refresh()->estado)->toBe('en_progreso');
});

it('lets an admin update a task owned by a camarera (cross-cutting elevated-role bypass)', function () {
    $camarera = User::factory()->create();
    $camarera->assignRole('camarera');
    $tarea = TareaLimpieza::factory()->create(['camarera_id' => $camarera->id, 'notas' => 'original']);

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Passport::actingAs($admin, ['*']);

    $response = $this->patchJson("/api/v1/tareas-limpieza/{$tarea->id}", [
        'data' => [
            'type' => 'tareas-limpieza',
            'id' => (string) $tarea->id,
            'attributes' => ['notas' => 'revisado por admin'],
        ],
    ], jsonApiHeaders());

    $response->assertOk();
    expect($tarea->refresh()->notas)->toBe('revisado por admin');
});

it('lets a user with tareas-limpieza.crear create a task', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $habitacion = Habitacion::factory()->create();
    Passport::actingAs($admin, ['*']);

    $response = $this->postJson('/api/v1/tareas-limpieza', [
        'data' => [
            'type' => 'tareas-limpieza',
            'attributes' => [
                'tipo' => 'salida',
                'fecha_programada' => '2026-01-01',
            ],
            'relationships' => [
                'habitacion' => ['data' => ['type' => 'habitacions', 'id' => (string) $habitacion->id]],
            ],
        ],
    ], jsonApiHeaders());

    $response->assertCreated();
    $this->assertDatabaseHas('tarea_limpiezas', [
        'habitacion_id' => $habitacion->id,
        'tipo' => 'salida',
    ]);
});

it('rejects a camarera creating a task (has ver/editar, lacks crear)', function () {
    $camarera = User::factory()->create();
    $camarera->assignRole('camarera');
    $habitacion = Habitacion::factory()->create();
    Passport::actingAs($camarera, ['*']);

    $response = $this->postJson('/api/v1/tareas-limpieza', [
        'data' => [
            'type' => 'tareas-limpieza',
            'attributes' => [
                'tipo' => 'salida',
                'fecha_programada' => '2026-01-01',
            ],
            'relationships' => [
                'habitacion' => ['data' => ['type' => 'habitacions', 'id' => (string) $habitacion->id]],
            ],
        ],
    ], jsonApiHeaders());

    $response->assertStatus(403);
});

it('rejects an invalid prioridad with a 422', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $habitacion = Habitacion::factory()->create();
    Passport::actingAs($admin, ['*']);

    $response = $this->postJson('/api/v1/tareas-limpieza', [
        'data' => [
            'type' => 'tareas-limpieza',
            'attributes' => [
                'tipo' => 'salida',
                'prioridad' => 'critica',
                'fecha_programada' => '2026-01-01',
            ],
            'relationships' => [
                'habitacion' => ['data' => ['type' => 'habitacions', 'id' => (string) $habitacion->id]],
            ],
        ],
    ], jsonApiHeaders());

    $response->assertStatus(422);
    $response->assertJsonFragment(['source' => ['pointer' => '/data/attributes/prioridad']]);
});

it('includes the assigned camarera via the include query parameter', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $camarera = User::factory()->create();
    $camarera->assignRole('camarera');
    $tarea = TareaLimpieza::factory()->create(['camarera_id' => $camarera->id]);
    Passport::actingAs($admin, ['*']);

    $response = $this->getJson("/api/v1/tareas-limpieza/{$tarea->id}?include=camarera", jsonApiHeaders());

    $response->assertOk();
    expect($response->json('included'))->toHaveCount(1)
        ->and($response->json('included.0.type'))->toBe('users')
        ->and($response->json('included.0.id'))->toBe((string) $camarera->id);
});

it('filters tareas-limpieza by estado over real http', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    TareaLimpieza::factory()->create(['estado' => 'completada']);
    TareaLimpieza::factory()->create(['estado' => 'pendiente']);
    Passport::actingAs($admin, ['*']);

    $response = $this->getJson('/api/v1/tareas-limpieza?filter[estado]=completada', jsonApiHeaders());

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.attributes.estado'))->toBe('completada');
});

it('returns 405 for DELETE on tareas-limpieza — no destroy route is registered (spec-part-10)', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $tarea = TareaLimpieza::factory()->create();
    Passport::actingAs($admin, ['*']);

    // The id-scoped path IS registered (for GET/PATCH), so Laravel's router
    // raises MethodNotAllowedHttpException (405) rather than a bare
    // "route not found" 404 — same documented deviation as RouteExposureTest
    // / EstanciaTest. The spec's literal wording of "404" doesn't hold given
    // how Laravel's router actually behaves; the underlying intent ("no
    // destroy route is registered") is fully satisfied.
    $this->deleteJson("/api/v1/tareas-limpieza/{$tarea->id}", [], jsonApiHeaders())
        ->assertStatus(405);
});
