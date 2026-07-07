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
 * Phase 7.2 note: `vendor/bin/sail artisan route:list --path=api --except-vendor`
 * shows ONLY `GET|HEAD api/user` — the `--except-vendor` flag hides every
 * JSON:API route because they are registered via the
 * `laravel-json-api/laravel` package's `JsonApiRoute::server()` macro
 * (attributed to vendor code in the route's "action" metadata), even though
 * the call site is `routes/api.php` itself. Dropping `--except-vendor`
 * (`route:list --path=api`) shows the full, correct 20-route table:
 * `rondas-enfermeria`, `visitas-habitacion`, `checklist-enfermeria`, and
 * `reportes-enfermeria` each expose exactly index/store/show/update (GET,
 * POST, GET/{id}, PATCH/{id}); `alertas-ronda` exposes only index/show/update
 * (no POST); `DELETE` is not registered for any of the 5 resources; plus the
 * pre-existing `GET|HEAD api/user`. This test file exercises that same
 * surface over real HTTP so a regression is caught by `artisan test`, not
 * just by eyeballing `route:list`.
 *
 * TWO ADDITIONAL EMPIRICALLY-CONFIRMED DEVIATIONS surfaced while writing
 * this file (on top of the two already documented in
 * `RondaEnfermeriaValidationTest.php` / `AlertaRondaAtendidoPorOverrideTest.php`):
 * 1. `DELETE /api/v1/{type}/{id}` returns 405, not 404, for ALL 5
 *    resources — the id-scoped path IS registered (for GET/PATCH), so
 *    Laravel's router raises `MethodNotAllowedHttpException` rather than
 *    a "route not found" 404. A bare 404 would only occur for a path with
 *    no matching template at all. This is the same class of deviation as
 *    the already-documented `POST /api/v1/alertas-ronda` -> 405 case; the
 *    spec's literal wording of "404 Not Found" for deletion doesn't hold
 *    given how Laravel's router actually behaves, but the underlying
 *    intent ("no destroy route is registered") is fully satisfied.
 * 2. A JSON:API resource object's `attributes` member, when present, MUST
 *    be a JSON *object* per the spec-compliance layer — PHP's `[]` encodes
 *    to a JSON array, not `{}`, so submitting `'attributes' => []` is
 *    itself a non-compliant document (400 "Non-Compliant JSON:API
 *    Document"), before Form Request rules ever run. Omitting the
 *    `attributes` key entirely (rather than sending an empty one) is the
 *    document-compliant way to submit "no attributes".
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function actingAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    Passport::actingAs($admin, ['*']);

    return $admin;
}

it('has index, show, store and update registered for the 4 mutable resources', function (string $type, callable $makeRecord) {
    actingAdmin();
    $record = $makeRecord();

    // index — registered (200), never 404.
    $this->getJson("/api/v1/{$type}", jsonApiHeaders())->assertStatus(200);

    // show — registered (200), never 404.
    $this->getJson("/api/v1/{$type}/{$record->id}", jsonApiHeaders())->assertStatus(200);

    // store — registered: a payload with no attributes at all still
    // reaches Form Request validation (422 for missing required fields),
    // proving the route exists rather than 404ing.
    $this->postJson("/api/v1/{$type}", [
        'data' => ['type' => $type],
    ], jsonApiHeaders())->assertStatus(422);

    // update — registered: a no-op partial update (no attributes member)
    // reaches the controller and succeeds (200), proving the route exists
    // rather than 404ing.
    $this->patchJson("/api/v1/{$type}/{$record->id}", [
        'data' => ['type' => $type, 'id' => (string) $record->id],
    ], jsonApiHeaders())->assertStatus(200);
})->with([
    'rondas-enfermeria' => ['rondas-enfermeria', fn () => RondaEnfermeria::factory()->create()],
    'visitas-habitacion' => ['visitas-habitacion', fn () => VisitaHabitacion::factory()->create()],
    'checklist-enfermeria' => ['checklist-enfermeria', fn () => ChecklistEnfermeriaItem::factory()->create()],
    'reportes-enfermeria' => ['reportes-enfermeria', fn () => ReporteEnfermeria::factory()->create(['estado' => 'borrador'])],
]);

it('has index, show and update registered for alertas-ronda but no store', function () {
    actingAdmin();
    $alerta = AlertaRonda::factory()->create();

    $this->getJson('/api/v1/alertas-ronda', jsonApiHeaders())->assertStatus(200);
    $this->getJson("/api/v1/alertas-ronda/{$alerta->id}", jsonApiHeaders())->assertStatus(200);

    $this->patchJson("/api/v1/alertas-ronda/{$alerta->id}", [
        'data' => ['type' => 'alertas-ronda', 'id' => (string) $alerta->id],
    ], jsonApiHeaders())->assertStatus(200);

    // No store route is registered for alertas-ronda. Because
    // `/api/v1/alertas-ronda` IS a registered path (for GET), Laravel's
    // router raises MethodNotAllowedHttpException for POST — 405, not 404.
    // See AlertaRondaAtendidoPorOverrideTest for the same documented
    // deviation.
    $this->postJson('/api/v1/alertas-ronda', [
        'data' => ['type' => 'alertas-ronda'],
    ], jsonApiHeaders())->assertStatus(405);
});

it('returns 405 for DELETE on every one of the 5 resources — no destroy route exists anywhere, but the id-scoped path is otherwise registered', function (string $type, callable $makeRecord) {
    actingAdmin();
    $record = $makeRecord();

    $this->deleteJson("/api/v1/{$type}/{$record->id}", [], jsonApiHeaders())->assertStatus(405);
})->with([
    'rondas-enfermeria' => ['rondas-enfermeria', fn () => RondaEnfermeria::factory()->create()],
    'visitas-habitacion' => ['visitas-habitacion', fn () => VisitaHabitacion::factory()->create()],
    'checklist-enfermeria' => ['checklist-enfermeria', fn () => ChecklistEnfermeriaItem::factory()->create()],
    'reportes-enfermeria' => ['reportes-enfermeria', fn () => ReporteEnfermeria::factory()->create()],
    'alertas-ronda' => ['alertas-ronda', fn () => AlertaRonda::factory()->create()],
]);
