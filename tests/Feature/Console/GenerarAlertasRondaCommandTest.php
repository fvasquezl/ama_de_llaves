<?php

use App\Models\AlertaRonda;
use App\Models\Residente;
use App\Models\RondaEnfermeria;
use App\Models\User;
use App\Models\VisitaHabitacion;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

/**
 * Builds a `RondaEnfermeria` with explicit, non-randomized `fecha`/`hora_*`
 * overrides so detection-boundary assertions are exact, per design part-15's
 * testing strategy (explicit overrides over factory randomized defaults).
 *
 * @param  array<string, mixed>  $overrides
 */
function crearRonda(array $overrides = []): RondaEnfermeria
{
    return RondaEnfermeria::factory()->create(array_merge([
        'turno' => 'matutino',
        'fecha' => now()->toDateString(),
        'hora_inicio_programada' => '08:00:00',
        'hora_fin_programada' => '16:00:00',
        'estado' => 'en_curso',
    ], $overrides));
}

/**
 * @param  array<string, mixed>  $overrides
 */
function crearVisita(RondaEnfermeria $ronda, array $overrides = []): VisitaHabitacion
{
    // Explicitly create the `Residente` with a guaranteed-unique `curp`
    // instead of letting `VisitaHabitacionFactory`'s default nested
    // `Residente::factory()` pick one via `fake()->word()`, whose small,
    // finite word pool has a real collision chance across the several
    // `Residente` rows this test file cascades into per test (violating
    // `residentes_curp_unique`).
    return VisitaHabitacion::factory()->create(array_merge([
        'ronda_enfermeria_id' => $ronda->id,
        'residente_id' => Residente::factory()->create(['curp' => Str::upper(Str::random(18))])->id,
        'hora_programada' => '09:00:00',
        'nfc_verificado' => false,
        'nfc_escaneado_at' => null,
        'estado' => 'pendiente',
    ], $overrides));
}

it('creates a visita_tardia alert only after the 15-minute grace period elapses', function () {
    $ronda = crearRonda();
    $visita = crearVisita($ronda, ['hora_programada' => '09:00:00']);

    $hoy = now()->toDateString();

    $this->travelTo("{$hoy} 09:14:00");
    $this->artisan('enfermeria:generar-alertas')->assertSuccessful();

    expect(AlertaRonda::query()->where('tipo', 'visita_tardia')->count())->toBe(0);

    $this->travelTo("{$hoy} 09:16:00");
    $this->artisan('enfermeria:generar-alertas')->assertSuccessful();

    $this->assertDatabaseHas('alerta_rondas', [
        'ronda_enfermeria_id' => $ronda->id,
        'visita_habitacion_id' => $visita->id,
        'tipo' => 'visita_tardia',
    ]);
});

it('creates a visita_omitida alert at ronda end and supersedes the prior visita_tardia', function () {
    $ronda = crearRonda(['hora_fin_programada' => '10:00:00']);
    $visita = crearVisita($ronda, ['hora_programada' => '09:00:00']);

    $hoy = now()->toDateString();

    $this->travelTo("{$hoy} 09:16:00");
    $this->artisan('enfermeria:generar-alertas')->assertSuccessful();

    $this->assertDatabaseHas('alerta_rondas', [
        'visita_habitacion_id' => $visita->id,
        'tipo' => 'visita_tardia',
    ]);

    $this->travelTo("{$hoy} 10:01:00");
    $this->artisan('enfermeria:generar-alertas')->assertSuccessful();

    $this->assertDatabaseMissing('alerta_rondas', [
        'visita_habitacion_id' => $visita->id,
        'tipo' => 'visita_tardia',
    ]);

    expect(AlertaRonda::query()
        ->where('visita_habitacion_id', $visita->id)
        ->where('tipo', 'visita_omitida')
        ->count())->toBe(1);

    expect($visita->fresh()->estado)->toBe('omitida');
});

it('creates a turno_incompleto alert and marks the ronda incompleta when it ends still en_curso', function () {
    $ronda = crearRonda(['estado' => 'en_curso', 'hora_fin_programada' => '10:00:00']);
    crearVisita($ronda, ['estado' => 'completada', 'nfc_verificado' => true, 'nfc_escaneado_at' => now()]);

    $this->travelTo(now()->toDateString().' 10:01:00');
    $this->artisan('enfermeria:generar-alertas')->assertSuccessful();

    $this->assertDatabaseHas('alerta_rondas', [
        'ronda_enfermeria_id' => $ronda->id,
        'visita_habitacion_id' => null,
        'tipo' => 'turno_incompleto',
    ]);

    expect($ronda->fresh()->estado)->toBe('incompleta');
});

it('does not duplicate alerts when the command runs twice in a row', function () {
    $ronda = crearRonda(['estado' => 'en_curso', 'hora_fin_programada' => '10:00:00']);
    crearVisita($ronda, ['estado' => 'completada', 'nfc_verificado' => true, 'nfc_escaneado_at' => now()]);

    $this->travelTo(now()->toDateString().' 10:01:00');

    $this->artisan('enfermeria:generar-alertas')->assertSuccessful();
    $countAfterFirstRun = AlertaRonda::query()->count();

    $this->artisan('enfermeria:generar-alertas')->assertSuccessful();
    $countAfterSecondRun = AlertaRonda::query()->count();

    expect($countAfterFirstRun)->toBe(1);
    expect($countAfterSecondRun)->toBe($countAfterFirstRun);
});

it('does not create a visita_tardia alert at exactly the 15-minute grace boundary, only strictly after it', function () {
    $ronda = crearRonda();
    $visita = crearVisita($ronda, ['hora_programada' => '09:00:00']);

    $hoy = now()->toDateString();

    $this->travelTo("{$hoy} 09:15:00");
    $this->artisan('enfermeria:generar-alertas')->assertSuccessful();

    expect(AlertaRonda::query()->where('visita_habitacion_id', $visita->id)->where('tipo', 'visita_tardia')->count())->toBe(0);

    $this->travelTo("{$hoy} 09:15:01");
    $this->artisan('enfermeria:generar-alertas')->assertSuccessful();

    expect(AlertaRonda::query()->where('visita_habitacion_id', $visita->id)->where('tipo', 'visita_tardia')->count())->toBe(1);
});

it('never flags a verified visit as visita_tardia even well past its hora_programada', function () {
    $ronda = crearRonda();
    $visita = crearVisita($ronda, [
        'hora_programada' => '09:00:00',
        'nfc_verificado' => true,
        'nfc_escaneado_at' => now(),
    ]);

    $this->travelTo(now()->toDateString().' 09:30:00');
    $this->artisan('enfermeria:generar-alertas')->assertSuccessful();

    expect(AlertaRonda::query()->where('visita_habitacion_id', $visita->id)->count())->toBe(0);
});

it('never flags a completada visit as visita_omitida even after hora_fin_programada passes', function () {
    $ronda = crearRonda(['hora_fin_programada' => '10:00:00', 'estado' => 'completada']);
    $visita = crearVisita($ronda, [
        'estado' => 'completada',
        'nfc_verificado' => true,
        'nfc_escaneado_at' => now(),
    ]);

    $this->travelTo(now()->toDateString().' 10:01:00');
    $this->artisan('enfermeria:generar-alertas')->assertSuccessful();

    expect(AlertaRonda::query()->where('visita_habitacion_id', $visita->id)->where('tipo', 'visita_omitida')->count())->toBe(0);
    expect($visita->fresh()->estado)->toBe('completada');
});

it('raises no turno_incompleto alert when a round completes on time with all visits completada', function () {
    $ronda = crearRonda(['hora_fin_programada' => '10:00:00', 'estado' => 'completada']);
    crearVisita($ronda, ['hora_programada' => '09:00:00', 'estado' => 'completada', 'nfc_verificado' => true, 'nfc_escaneado_at' => now()]);
    crearVisita($ronda, ['hora_programada' => '09:30:00', 'estado' => 'completada', 'nfc_verificado' => true, 'nfc_escaneado_at' => now()]);

    $this->travelTo(now()->toDateString().' 10:01:00');
    $this->artisan('enfermeria:generar-alertas')->assertSuccessful();

    expect(AlertaRonda::query()->where('ronda_enfermeria_id', $ronda->id)->where('tipo', 'turno_incompleto')->count())->toBe(0);
    expect($ronda->fresh()->estado)->toBe('completada');
});

it('does not fire turno_incompleto for a round already marked completada even with a lingering non-terminal child visit (accepted sweep-ordering behavior, spec-part-05 amendment)', function () {
    $ronda = crearRonda(['hora_fin_programada' => '10:00:00', 'estado' => 'completada']);
    $visita = crearVisita($ronda, ['estado' => 'pendiente', 'nfc_verificado' => false]);

    $this->travelTo(now()->toDateString().' 10:01:00');
    $this->artisan('enfermeria:generar-alertas')->assertSuccessful();

    // Sweep 2 (visita_omitida) resolves the lingering child visit to a
    // terminal state before sweep 3 (turno_incompleto) ever queries the
    // round again, so the round no longer matches either branch of its
    // query. This is documented, accepted behavior (see the PHPDoc on
    // detectarTurnosIncompletos() and spec-part-05's amendment) — not a
    // gap to fix.
    expect($visita->fresh()->estado)->toBe('omitida');
    expect(AlertaRonda::query()->where('visita_habitacion_id', $visita->id)->where('tipo', 'visita_omitida')->count())->toBe(1);
    expect(AlertaRonda::query()->where('ronda_enfermeria_id', $ronda->id)->where('tipo', 'turno_incompleto')->count())->toBe(0);
    expect($ronda->fresh()->estado)->toBe('completada');
});

it('correctly detects visitas tardías and turno_incompleto across a midnight-crossing nocturno round', function () {
    $hoy = now()->toDateString();
    $manana = Carbon::parse($hoy)->addDay()->toDateString();

    $ronda = crearRonda([
        'turno' => 'nocturno',
        'fecha' => $hoy,
        'hora_inicio_programada' => '22:00:00',
        'hora_fin_programada' => '06:00:00',
        'estado' => 'en_curso',
    ]);
    $visitaA = crearVisita($ronda, ['hora_programada' => '23:30:00']);
    $visitaB = crearVisita($ronda, ['hora_programada' => '02:00:00']);

    // Same calendar day as `fecha`, before the shift even starts (22:00).
    // A naive same-day (no rollover) reading of visita B's "02:00" would
    // wrongly compute a limit around this instant — confirm it does NOT.
    $this->travelTo("{$hoy} 02:16:00");
    $this->artisan('enfermeria:generar-alertas')->assertSuccessful();
    expect(AlertaRonda::query()->where('visita_habitacion_id', $visitaB->id)->count())->toBe(0);

    // Same calendar day, before the shift even starts — a naive same-day
    // reading of hora_fin_programada ("06:00") would already have passed
    // by 06:01; confirm the round is NOT flagged (true boundary is D+1).
    $this->travelTo("{$hoy} 06:01:00");
    $this->artisan('enfermeria:generar-alertas')->assertSuccessful();
    expect(AlertaRonda::query()->where('ronda_enfermeria_id', $ronda->id)->where('tipo', 'turno_incompleto')->count())->toBe(0);

    // 16 minutes past visita A's true instant (D 23:30) — fires for A only.
    $this->travelTo("{$hoy} 23:46:00");
    $this->artisan('enfermeria:generar-alertas')->assertSuccessful();
    expect(AlertaRonda::query()->where('visita_habitacion_id', $visitaA->id)->where('tipo', 'visita_tardia')->count())->toBe(1);
    expect(AlertaRonda::query()->where('visita_habitacion_id', $visitaB->id)->where('tipo', 'visita_tardia')->count())->toBe(0);

    // 16 minutes past visita B's true, rolled-over instant (D+1 02:00).
    $this->travelTo("{$manana} 02:16:00");
    $this->artisan('enfermeria:generar-alertas')->assertSuccessful();
    expect(AlertaRonda::query()->where('visita_habitacion_id', $visitaB->id)->where('tipo', 'visita_tardia')->count())->toBe(1);

    // Round's true hora_fin_programada boundary (D+1 06:00) has now passed.
    $this->travelTo("{$manana} 06:01:00");
    $this->artisan('enfermeria:generar-alertas')->assertSuccessful();
    expect(AlertaRonda::query()->where('ronda_enfermeria_id', $ronda->id)->where('tipo', 'turno_incompleto')->count())->toBe(1);
    expect($ronda->fresh()->estado)->toBe('incompleta');
});

it('produces no duplicate alerts across all three alert types when the command runs twice with no time travel between runs', function () {
    $ronda = crearRonda(['hora_fin_programada' => '10:00:00', 'estado' => 'en_curso']);
    crearVisita($ronda, ['hora_programada' => '09:00:00', 'estado' => 'pendiente', 'nfc_verificado' => false]);
    crearVisita($ronda, ['hora_programada' => '09:30:00', 'estado' => 'completada', 'nfc_verificado' => true, 'nfc_escaneado_at' => now()]);

    $this->travelTo(now()->toDateString().' 10:01:00');

    $this->artisan('enfermeria:generar-alertas')->assertSuccessful();
    $countAfterFirstRun = AlertaRonda::query()->count();

    $this->artisan('enfermeria:generar-alertas')->assertSuccessful();
    $countAfterSecondRun = AlertaRonda::query()->count();

    // 1 visita_omitida (the pending visit, superseding its own visita_tardia
    // created earlier in the same first run) + 1 turno_incompleto.
    expect($countAfterFirstRun)->toBe(2);
    expect($countAfterSecondRun)->toBe($countAfterFirstRun);
});

it('dispatches no notification or mail when creating alerts and mutating estado', function () {
    Notification::fake();
    Mail::fake();

    $ronda = crearRonda(['hora_fin_programada' => '10:00:00']);
    crearVisita($ronda, ['hora_programada' => '09:00:00']);

    $this->travelTo(now()->toDateString().' 10:01:00');
    $this->artisan('enfermeria:generar-alertas')->assertSuccessful();

    expect(AlertaRonda::query()->count())->toBeGreaterThan(0);

    Notification::assertNothingSent();
    Mail::assertNothingSent();
});

it('leaves nfc-verified, completada, and omitida rows untouched by the visita_tardia sweep', function () {
    $ronda = crearRonda(['hora_fin_programada' => '16:00:00', 'estado' => 'en_curso']);
    $verificada = crearVisita($ronda, ['hora_programada' => '09:00:00', 'nfc_verificado' => true, 'nfc_escaneado_at' => now(), 'estado' => 'pendiente']);
    $completada = crearVisita($ronda, ['hora_programada' => '09:30:00', 'estado' => 'completada', 'nfc_verificado' => true, 'nfc_escaneado_at' => now()]);
    $omitida = crearVisita($ronda, ['hora_programada' => '10:00:00', 'estado' => 'omitida', 'nfc_verificado' => false]);

    $this->travelTo(now()->toDateString().' 12:00:00');
    $this->artisan('enfermeria:generar-alertas')->assertSuccessful();

    expect(AlertaRonda::query()->where('visita_habitacion_id', $verificada->id)->exists())->toBeFalse();
    expect(AlertaRonda::query()->where('visita_habitacion_id', $completada->id)->exists())->toBeFalse();
    expect(AlertaRonda::query()->where('visita_habitacion_id', $omitida->id)->exists())->toBeFalse();

    expect($verificada->fresh()->estado)->toBe('pendiente');
    expect($completada->fresh()->estado)->toBe('completada');
    expect($omitida->fresh()->estado)->toBe('omitida');
});

it('lets a manual PATCH set estado=completada before hora_fin_programada passes, unaffected by and preventing the automated omitida sweep', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $enfermera = User::factory()->create();
    $enfermera->assignRole('enfermera');
    $ronda = crearRonda(['hora_fin_programada' => '10:00:00', 'enfermera_id' => $enfermera->id]);
    $visita = crearVisita($ronda, ['hora_programada' => '09:00:00']);

    Passport::actingAs($enfermera, ['*']);

    $this->travelTo(now()->toDateString().' 09:05:00');

    $response = $this->patchJson("/api/v1/visitas-habitacion/{$visita->id}", [
        'data' => [
            'type' => 'visitas-habitacion',
            'id' => (string) $visita->id,
            'attributes' => ['estado' => 'completada'],
        ],
    ], jsonApiHeaders());

    $response->assertStatus(200);
    expect($visita->fresh()->estado)->toBe('completada');

    $this->travelTo(now()->toDateString().' 10:01:00');
    $this->artisan('enfermeria:generar-alertas')->assertSuccessful();

    expect(AlertaRonda::query()->where('visita_habitacion_id', $visita->id)->count())->toBe(0);
    expect($visita->fresh()->estado)->toBe('completada');
});

it('includes a round exactly at the 2-day sweep-window cutoff but silently skips one further back (documented open item, not a bug — design part-17 / tasks 7.5)', function () {
    $hoy = now()->toDateString();

    $rondaDentroDeVentana = crearRonda([
        'fecha' => Carbon::parse($hoy)->subDays(2)->toDateString(),
        'estado' => 'en_curso',
        'hora_fin_programada' => '10:00:00',
    ]);
    $rondaFueraDeVentana = crearRonda([
        'fecha' => Carbon::parse($hoy)->subDays(3)->toDateString(),
        'estado' => 'en_curso',
        'hora_fin_programada' => '10:00:00',
    ]);

    $this->travelTo("{$hoy} 12:00:00");
    $this->artisan('enfermeria:generar-alertas')->assertSuccessful();

    expect(AlertaRonda::query()->where('ronda_enfermeria_id', $rondaDentroDeVentana->id)->where('tipo', 'turno_incompleto')->count())->toBe(1);
    expect($rondaDentroDeVentana->fresh()->estado)->toBe('incompleta');

    // Accepted, deliberately-deprioritized limitation: the sweep queries
    // bound `fecha >= now()->subDays(2)` for performance. A round one day
    // further back than that cutoff is silently excluded from every sweep
    // even though its hora_fin_programada has long since passed. Do not
    // "fix" this without a design revisit.
    expect(AlertaRonda::query()->where('ronda_enfermeria_id', $rondaFueraDeVentana->id)->count())->toBe(0);
    expect($rondaFueraDeVentana->fresh()->estado)->toBe('en_curso');
});
