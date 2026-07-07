<?php

use App\Console\Commands\GenerarAlertasRondaCommand;
use App\Models\RondaEnfermeria;
use Carbon\Carbon;
use Tests\TestCase;

// This suite lives in tests/Unit, which — per tests/Pest.php — does NOT
// extend the Laravel TestCase by default. Eloquent's `date` cast on
// `fecha` needs a bound DB connection resolver to format the value (even
// though no query is ever executed here), so this file opts back into
// TestCase to boot the app container. No DB access happens: attributes are
// set directly via forceFill() below rather than through a factory or a
// persisted/refreshed record, keeping the test isolated from the database.
uses(TestCase::class);

/**
 * Builds a plain (unsaved, unfactoried) RondaEnfermeria instance.
 *
 * @param  array<string, mixed>  $attributes
 */
function makeRonda(array $attributes): RondaEnfermeria
{
    return (new RondaEnfermeria)->forceFill($attributes);
}

/**
 * Invokes the private `combineWithRondaWindow` helper via reflection, since
 * Phase 2 deliberately builds and proves this rollover-aware date math in
 * isolation before the three detection sweeps (Phase 3) exist to call it.
 */
function invokeCombineWithRondaWindow(RondaEnfermeria $ronda, string $hora): Carbon
{
    $command = new GenerarAlertasRondaCommand;

    $method = new ReflectionMethod($command, 'combineWithRondaWindow');
    $method->setAccessible(true);

    return $method->invoke($command, $ronda, $hora);
}

it('combines a diurno-style round window on the same day without rolling over', function () {
    $ronda = makeRonda([
        'turno' => 'matutino',
        'fecha' => '2026-07-07',
        'hora_inicio_programada' => '07:00:00',
        'hora_fin_programada' => '15:00:00',
    ]);

    $instant = invokeCombineWithRondaWindow($ronda, '09:00:00');

    expect($instant->toDateTimeString())->toBe('2026-07-07 09:00:00');
});

it('rolls a nocturno round hora_fin_programada to the next calendar day', function () {
    $ronda = makeRonda([
        'turno' => 'nocturno',
        'fecha' => '2026-07-07',
        'hora_inicio_programada' => '22:00:00',
        'hora_fin_programada' => '06:00:00',
    ]);

    $instant = invokeCombineWithRondaWindow($ronda, $ronda->hora_fin_programada);

    expect($instant->toDateTimeString())->toBe('2026-07-08 06:00:00');
});

it('does not roll a visit scheduled before midnight within a nocturno round', function () {
    // Spec-part-06 scenario: hora_programada=23:50 is still on `fecha`
    // itself — the non-rolled leg of the "visit crosses midnight" case.
    $ronda = makeRonda([
        'turno' => 'nocturno',
        'fecha' => '2026-07-07',
        'hora_inicio_programada' => '22:00:00',
        'hora_fin_programada' => '06:00:00',
    ]);

    $instant = invokeCombineWithRondaWindow($ronda, '23:50:00');

    expect($instant->toDateTimeString())->toBe('2026-07-07 23:50:00');
});

it('rolls a visit scheduled after midnight within a nocturno round to the next calendar day', function () {
    $ronda = makeRonda([
        'turno' => 'nocturno',
        'fecha' => '2026-07-07',
        'hora_inicio_programada' => '22:00:00',
        'hora_fin_programada' => '06:00:00',
    ]);

    $instant = invokeCombineWithRondaWindow($ronda, '02:00:00');

    expect($instant->toDateTimeString())->toBe('2026-07-08 02:00:00');
});

it('does not roll over a zero-duration round where hora_fin_programada equals hora_inicio_programada', function () {
    // Exercises design part-17's noted-but-deprioritized open item: the
    // generic lessThan() rule degrades safely (non-rolling) for a
    // zero-duration round rather than erroring; no new handling is added.
    $ronda = makeRonda([
        'turno' => 'nocturno',
        'fecha' => '2026-07-07',
        'hora_inicio_programada' => '08:00:00',
        'hora_fin_programada' => '08:00:00',
    ]);

    $instant = invokeCombineWithRondaWindow($ronda, $ronda->hora_fin_programada);

    expect($instant->toDateTimeString())->toBe('2026-07-07 08:00:00');
});
