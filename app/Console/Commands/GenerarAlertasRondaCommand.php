<?php

namespace App\Console\Commands;

use App\Models\AlertaRonda;
use App\Models\RondaEnfermeria;
use App\Models\VisitaHabitacion;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('enfermeria:generar-alertas')]
#[Description('Detecta visitas tardías/omitidas y rondas incompletas, generando AlertaRonda.')]
class GenerarAlertasRondaCommand extends Command
{
    /**
     * Execute the console command.
     *
     * Runs the three detection sweeps against a single, consistent `now`
     * captured once here (not re-read per sweep), then persists any
     * qualifying `AlertaRonda` rows and `estado` mutations.
     */
    public function handle(): int
    {
        $now = Carbon::now();

        $this->detectarVisitasTardias($now);
        $this->detectarVisitasOmitidas($now);
        $this->detectarTurnosIncompletos($now);

        return self::SUCCESS;
    }

    /**
     * Sweep 1: flag visits still `pendiente`/unverified more than 15 minutes
     * past their scheduled instant (spec-part-03).
     */
    private function detectarVisitasTardias(Carbon $now): void
    {
        VisitaHabitacion::query()
            ->where('estado', 'pendiente')
            ->where('nfc_verificado', false)
            ->whereHas('rondaEnfermeria', fn ($query) => $query->whereDate('fecha', '>=', $now->copy()->subDays(2)))
            ->with('rondaEnfermeria')
            ->get()
            ->each(function (VisitaHabitacion $visita) use ($now): void {
                $limite = $this->combineWithRondaWindow($visita->rondaEnfermeria, $visita->hora_programada)
                    ->addMinutes(15);

                if ($limite->greaterThanOrEqualTo($now)) {
                    return;
                }

                DB::transaction(function () use ($visita): void {
                    AlertaRonda::firstOrCreate([
                        'ronda_enfermeria_id' => $visita->ronda_enfermeria_id,
                        'visita_habitacion_id' => $visita->id,
                        'tipo' => 'visita_tardia',
                    ]);
                });
            });
    }

    /**
     * Sweep 2: flag visits that are still not terminal once their round's
     * `hora_fin_programada` passes, superseding any prior `visita_tardia`
     * alert for the same visit via hard delete-then-create (spec-part-04).
     */
    private function detectarVisitasOmitidas(Carbon $now): void
    {
        VisitaHabitacion::query()
            ->whereNotIn('estado', ['completada', 'omitida'])
            ->whereHas('rondaEnfermeria', fn ($query) => $query->whereDate('fecha', '>=', $now->copy()->subDays(2)))
            ->with('rondaEnfermeria')
            ->get()
            ->each(function (VisitaHabitacion $visita) use ($now): void {
                $finProgramada = $this->combineWithRondaWindow($visita->rondaEnfermeria, $visita->rondaEnfermeria->hora_fin_programada);

                if ($finProgramada->greaterThan($now)) {
                    return;
                }

                DB::transaction(function () use ($visita): void {
                    AlertaRonda::query()
                        ->where('ronda_enfermeria_id', $visita->ronda_enfermeria_id)
                        ->where('visita_habitacion_id', $visita->id)
                        ->where('tipo', 'visita_tardia')
                        ->delete();

                    AlertaRonda::firstOrCreate([
                        'ronda_enfermeria_id' => $visita->ronda_enfermeria_id,
                        'visita_habitacion_id' => $visita->id,
                        'tipo' => 'visita_omitida',
                    ]);

                    $visita->update(['estado' => 'omitida']);
                });
            });
    }

    /**
     * Sweep 3: flag rounds whose `hora_fin_programada` has passed while
     * still `pendiente`/`en_curso` (branch 1), or while a child visit
     * remains non-terminal (branch 2, spec-part-05).
     *
     * KNOWN ORDERING INTERACTION (accepted, not a bug): this sweep runs
     * after `detectarVisitasOmitidas`, and both gate on the same round
     * `hora_fin_programada` boundary. By the time this method runs, any
     * child visit that was still non-terminal at that boundary has already
     * been flipped to the terminal `omitida` state by sweep 2. This means
     * branch 2 above — a round whose own `estado` is already terminal
     * (e.g. manually marked `completada`) but which still has a lingering
     * non-terminal child visit — is effectively unreachable in practice:
     * the `orWhereHas` clause below can only still match on a round that
     * *also* satisfies branch 1 (`estado` still `pendiente`/`en_curso`),
     * since sweep 2 has already resolved every other non-terminal visit.
     *
     * This is intentional, not silently dropped: a `RondaEnfermeria`
     * manually marked `completada` by a human while a child visit remains
     * incomplete is itself an inconsistent state that arguably should not
     * be auto-flagged as `turno_incompleto` by this scheduled job — the
     * `visita_omitida` alert already raised on that lingering visit
     * surfaces the problem. See spec-part-05's amendment note on the
     * "Round end passes with a non-terminal child visit" scenario for the
     * accepted resolution. Only branch 1 is exercised by the test suite;
     * do not mistake the absence of a branch-2-only test for a gap.
     */
    private function detectarTurnosIncompletos(Carbon $now): void
    {
        RondaEnfermeria::query()
            ->where(function ($query) {
                $query->whereIn('estado', ['pendiente', 'en_curso'])
                    ->orWhereHas('visitaHabitacions', fn ($q) => $q->whereNotIn('estado', ['completada', 'omitida']));
            })
            ->whereDate('fecha', '>=', $now->copy()->subDays(2))
            ->with('visitaHabitacions')
            ->get()
            ->each(function (RondaEnfermeria $ronda) use ($now): void {
                $finProgramada = $this->combineWithRondaWindow($ronda, $ronda->hora_fin_programada);

                if ($finProgramada->greaterThan($now)) {
                    return;
                }

                DB::transaction(function () use ($ronda): void {
                    AlertaRonda::firstOrCreate([
                        'ronda_enfermeria_id' => $ronda->id,
                        'visita_habitacion_id' => null,
                        'tipo' => 'turno_incompleto',
                    ]);

                    $ronda->update(['estado' => 'incompleta']);
                });
            });
    }

    /**
     * Combine a round's date-only `fecha` with a time-only `hora_*` column
     * into a single comparable Carbon instant on that same calendar day.
     */
    private function combineFechaHora(Carbon $fecha, string $hora): Carbon
    {
        return Carbon::parse($fecha->format('Y-m-d').' '.$hora);
    }

    /**
     * Combine a round's `fecha` with any of its `hora_*` time-only columns
     * (or a child visit's `hora_programada`), rolling the result to the
     * next calendar day whenever it falls before the round's own
     * `hora_inicio_programada` instant — which happens exactly when a
     * `nocturno` shift crosses midnight. The rollover is derived purely
     * from comparing clock-times, with no branching on `turno`.
     */
    private function combineWithRondaWindow(RondaEnfermeria $ronda, string $hora): Carbon
    {
        $inicio = $this->combineFechaHora($ronda->fecha, $ronda->hora_inicio_programada);
        $instant = $this->combineFechaHora($ronda->fecha, $hora);

        if ($instant->lessThan($inicio)) {
            $instant->addDay();
        }

        return $instant;
    }
}
