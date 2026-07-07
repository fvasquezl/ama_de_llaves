<?php

use App\Models\RondaEnfermeria;
use App\Models\VisitaHabitacion;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Migration-level test: the two DB unique constraints added by
 * add_unique_constraints_to_alerta_rondas_table must reject duplicate
 * inserts on their own, independent of any app-level guard
 * (firstOrCreate/delete-then-create) implemented in later phases.
 */
it('rejects a duplicate (ronda_enfermeria_id, visita_habitacion_id, tipo) tuple at the DB level', function () {
    $ronda = RondaEnfermeria::factory()->create();
    $visita = VisitaHabitacion::factory()->create();

    DB::table('alerta_rondas')->insert([
        'ronda_enfermeria_id' => $ronda->id,
        'visita_habitacion_id' => $visita->id,
        'tipo' => 'visita_tardia',
        'atendido' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => DB::table('alerta_rondas')->insert([
        'ronda_enfermeria_id' => $ronda->id,
        'visita_habitacion_id' => $visita->id,
        'tipo' => 'visita_tardia',
        'atendido' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

it('allows the same ronda/visita pair to have alerts of a different tipo', function () {
    $ronda = RondaEnfermeria::factory()->create();
    $visita = VisitaHabitacion::factory()->create();

    DB::table('alerta_rondas')->insert([
        'ronda_enfermeria_id' => $ronda->id,
        'visita_habitacion_id' => $visita->id,
        'tipo' => 'visita_tardia',
        'atendido' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('alerta_rondas')->insert([
        'ronda_enfermeria_id' => $ronda->id,
        'visita_habitacion_id' => $visita->id,
        'tipo' => 'visita_omitida',
        'atendido' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(DB::table('alerta_rondas')->count())->toBe(2);
});

it('rejects a second turno_incompleto alert for the same ronda even though visita_habitacion_id is null for both', function () {
    $ronda = RondaEnfermeria::factory()->create();

    DB::table('alerta_rondas')->insert([
        'ronda_enfermeria_id' => $ronda->id,
        'visita_habitacion_id' => null,
        'tipo' => 'turno_incompleto',
        'atendido' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => DB::table('alerta_rondas')->insert([
        'ronda_enfermeria_id' => $ronda->id,
        'visita_habitacion_id' => null,
        'tipo' => 'turno_incompleto',
        'atendido' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

it('allows turno_incompleto alerts for different rondas despite both having a null visita_habitacion_id', function () {
    $primeraRonda = RondaEnfermeria::factory()->create();
    $segundaRonda = RondaEnfermeria::factory()->create();

    DB::table('alerta_rondas')->insert([
        'ronda_enfermeria_id' => $primeraRonda->id,
        'visita_habitacion_id' => null,
        'tipo' => 'turno_incompleto',
        'atendido' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('alerta_rondas')->insert([
        'ronda_enfermeria_id' => $segundaRonda->id,
        'visita_habitacion_id' => null,
        'tipo' => 'turno_incompleto',
        'atendido' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(DB::table('alerta_rondas')->count())->toBe(2);
});
