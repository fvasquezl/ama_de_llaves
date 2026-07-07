<?php

use App\Models\AlertaRonda;
use App\Models\ChecklistEnfermeriaItem;
use App\Models\ReporteEnfermeria;
use App\Models\RondaEnfermeria;
use App\Models\VisitaHabitacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LaravelJsonApi\Contracts\Server\Repository as ServerRepository;

uses(RefreshDatabase::class);

/**
 * These tests exercise the Schema `fields()`/`filters()` definitions
 * directly against the Eloquent layer. There are no HTTP routes mounted
 * for these resources yet (routing is Phase 7 and Form Request
 * validation is Phase 6 of the enfermeria-api change), so full
 * request/response JSON:API integration tests belong in Phase 8. What
 * is verified here is that the mechanisms the future HTTP layer will
 * rely on — include-path eager loading and filter query application —
 * are wired correctly for each of the 5 resources.
 */
function schemaFor(string $resourceType)
{
    return app(ServerRepository::class)->server('v1')->schemas()->schemaFor($resourceType);
}

it('includes visitaHabitacions when loading a ronda-enfermeria', function () {
    $ronda = RondaEnfermeria::factory()->create();
    VisitaHabitacion::factory()->count(2)->create(['ronda_enfermeria_id' => $ronda->id]);

    $schema = schemaFor('rondas-enfermeria');
    $schema->loaderFor($ronda)->load('visitaHabitacions');

    expect($ronda->relationLoaded('visitaHabitacions'))->toBeTrue()
        ->and($ronda->visitaHabitacions)->toHaveCount(2);
});

it('includes checklistEnfermeriaItems when loading a visita-habitacion', function () {
    $visita = VisitaHabitacion::factory()->create();
    ChecklistEnfermeriaItem::factory()->count(3)->create(['visita_habitacion_id' => $visita->id]);

    $schema = schemaFor('visitas-habitacion');
    $schema->loaderFor($visita)->load('checklistEnfermeriaItems');

    expect($visita->relationLoaded('checklistEnfermeriaItems'))->toBeTrue()
        ->and($visita->checklistEnfermeriaItems)->toHaveCount(3);
});

it('includes visitaHabitacion when loading a checklist-enfermeria item', function () {
    $visita = VisitaHabitacion::factory()->create();
    $item = ChecklistEnfermeriaItem::factory()->create(['visita_habitacion_id' => $visita->id]);

    $schema = schemaFor('checklist-enfermeria');
    $schema->loaderFor($item)->load('visitaHabitacion');

    expect($item->relationLoaded('visitaHabitacion'))->toBeTrue()
        ->and($item->visitaHabitacion->is($visita))->toBeTrue();
});

it('includes rondaEnfermeria when loading a reporte-enfermeria', function () {
    $ronda = RondaEnfermeria::factory()->create();
    $reporte = ReporteEnfermeria::factory()->create(['ronda_enfermeria_id' => $ronda->id]);

    $schema = schemaFor('reportes-enfermeria');
    $schema->loaderFor($reporte)->load('rondaEnfermeria');

    expect($reporte->relationLoaded('rondaEnfermeria'))->toBeTrue()
        ->and($reporte->rondaEnfermeria->is($ronda))->toBeTrue();
});

it('includes rondaEnfermeria when loading an alerta-ronda', function () {
    $ronda = RondaEnfermeria::factory()->create();
    $alerta = AlertaRonda::factory()->create(['ronda_enfermeria_id' => $ronda->id]);

    $schema = schemaFor('alertas-ronda');
    $schema->loaderFor($alerta)->load('rondaEnfermeria');

    expect($alerta->relationLoaded('rondaEnfermeria'))->toBeTrue()
        ->and($alerta->rondaEnfermeria->is($ronda))->toBeTrue();
});

it('filters rondas-enfermeria by estado', function () {
    RondaEnfermeria::factory()->create(['estado' => 'completada']);
    RondaEnfermeria::factory()->create(['estado' => 'pendiente']);

    $schema = schemaFor('rondas-enfermeria');
    $filter = collect($schema->filters())->first(fn ($f) => $f->key() === 'estado');

    $results = $filter->apply(RondaEnfermeria::query(), 'completada')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->estado)->toBe('completada');
});

it('filters visitas-habitacion by nfc_verificado', function () {
    VisitaHabitacion::factory()->create(['nfc_verificado' => true]);
    VisitaHabitacion::factory()->create(['nfc_verificado' => false]);

    $schema = schemaFor('visitas-habitacion');
    $filter = collect($schema->filters())->first(fn ($f) => $f->key() === 'nfc_verificado');

    $results = $filter->apply(VisitaHabitacion::query(), 'true')->get();

    expect($results)->toHaveCount(1)
        ->and((bool) $results->first()->nfc_verificado)->toBeTrue();
});

it('filters checklist-enfermeria by completado', function () {
    ChecklistEnfermeriaItem::factory()->create(['completado' => true]);
    ChecklistEnfermeriaItem::factory()->create(['completado' => false]);

    $schema = schemaFor('checklist-enfermeria');
    $filter = collect($schema->filters())->first(fn ($f) => $f->key() === 'completado');

    $results = $filter->apply(ChecklistEnfermeriaItem::query(), 'true')->get();

    expect($results)->toHaveCount(1);
});

it('filters reportes-enfermeria by estado', function () {
    ReporteEnfermeria::factory()->create(['estado' => 'firmado']);
    ReporteEnfermeria::factory()->create(['estado' => 'borrador']);

    $schema = schemaFor('reportes-enfermeria');
    $filter = collect($schema->filters())->first(fn ($f) => $f->key() === 'estado');

    $results = $filter->apply(ReporteEnfermeria::query(), 'firmado')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->estado)->toBe('firmado');
});

it('filters alertas-ronda by atendido', function () {
    AlertaRonda::factory()->create(['atendido' => true]);
    AlertaRonda::factory()->create(['atendido' => false]);

    $schema = schemaFor('alertas-ronda');
    $filter = collect($schema->filters())->first(fn ($f) => $f->key() === 'atendido');

    $results = $filter->apply(AlertaRonda::query(), 'true')->get();

    expect($results)->toHaveCount(1)
        ->and((bool) $results->first()->atendido)->toBeTrue();
});
