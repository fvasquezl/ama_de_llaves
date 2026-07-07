<?php

use App\Models\AlertaRonda;
use App\Models\ChecklistEnfermeriaItem;
use App\Models\ReporteEnfermeria;
use App\Models\RondaEnfermeria;
use App\Models\User;
use App\Models\VisitaHabitacion;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Server\Repository as ServerRepository;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * Build a fake JSON:API index request "as" the given user, and run it
 * through the named schema's indexQuery() scoping hook.
 */
function scopedIndexResults(string $resourceType, User $user, $baseQuery)
{
    $server = app(ServerRepository::class)->server('v1');
    $schema = $server->schemas()->schemaFor($resourceType);

    $request = Request::create('/api/v1/'.$resourceType);
    $request->setUserResolver(fn () => $user);

    return $schema->indexQuery($request, $baseQuery)->get();
}

it('scopes rondas-enfermeria to the authenticated enfermera own rows', function () {
    $enfermeraA = User::factory()->create();
    $enfermeraA->assignRole('enfermera');
    $enfermeraB = User::factory()->create();
    $enfermeraB->assignRole('enfermera');

    RondaEnfermeria::factory()->count(2)->create(['enfermera_id' => $enfermeraA->id]);
    RondaEnfermeria::factory()->count(3)->create(['enfermera_id' => $enfermeraB->id]);

    $results = scopedIndexResults('rondas-enfermeria', $enfermeraA, RondaEnfermeria::query());

    expect($results)->toHaveCount(2)
        ->and($results->pluck('enfermera_id')->unique()->all())->toBe([$enfermeraA->id]);
});

it('does not scope rondas-enfermeria for supervisor, admin or super-admin', function () {
    $enfermeraA = User::factory()->create();
    $enfermeraA->assignRole('enfermera');
    $enfermeraB = User::factory()->create();
    $enfermeraB->assignRole('enfermera');

    RondaEnfermeria::factory()->count(2)->create(['enfermera_id' => $enfermeraA->id]);
    RondaEnfermeria::factory()->count(3)->create(['enfermera_id' => $enfermeraB->id]);

    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $superAdmin = User::factory()->superAdmin()->create();

    expect(scopedIndexResults('rondas-enfermeria', $supervisor, RondaEnfermeria::query()))->toHaveCount(5)
        ->and(scopedIndexResults('rondas-enfermeria', $admin, RondaEnfermeria::query()))->toHaveCount(5)
        ->and(scopedIndexResults('rondas-enfermeria', $superAdmin, RondaEnfermeria::query()))->toHaveCount(5);
});

it('scopes visitas-habitacion to visits under the enfermera own rounds (one-hop)', function () {
    $enfermeraA = User::factory()->create();
    $enfermeraA->assignRole('enfermera');
    $enfermeraB = User::factory()->create();
    $enfermeraB->assignRole('enfermera');

    $rondaA = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermeraA->id]);
    $rondaB = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermeraB->id]);

    VisitaHabitacion::factory()->count(2)->create(['ronda_enfermeria_id' => $rondaA->id]);
    VisitaHabitacion::factory()->count(3)->create(['ronda_enfermeria_id' => $rondaB->id]);

    $results = scopedIndexResults('visitas-habitacion', $enfermeraA, VisitaHabitacion::query());

    expect($results)->toHaveCount(2);
});

it('scopes checklist-enfermeria via the two-hop chain visitaHabitacion.rondaEnfermeria.enfermera_id', function () {
    $enfermeraA = User::factory()->create();
    $enfermeraA->assignRole('enfermera');
    $enfermeraB = User::factory()->create();
    $enfermeraB->assignRole('enfermera');

    $rondaA = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermeraA->id]);
    $rondaB = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermeraB->id]);

    $visitaA = VisitaHabitacion::factory()->create(['ronda_enfermeria_id' => $rondaA->id]);
    $visitaB = VisitaHabitacion::factory()->create(['ronda_enfermeria_id' => $rondaB->id]);

    ChecklistEnfermeriaItem::factory()->count(2)->create(['visita_habitacion_id' => $visitaA->id]);
    ChecklistEnfermeriaItem::factory()->count(4)->create(['visita_habitacion_id' => $visitaB->id]);

    $results = scopedIndexResults('checklist-enfermeria', $enfermeraA, ChecklistEnfermeriaItem::query());

    expect($results)->toHaveCount(2);
});

it('scopes reportes-enfermeria to reports authored by the enfermera (own enfermera_id, not the round owner)', function () {
    $enfermeraA = User::factory()->create();
    $enfermeraA->assignRole('enfermera');
    $enfermeraB = User::factory()->create();
    $enfermeraB->assignRole('enfermera');

    ReporteEnfermeria::factory()->count(2)->create(['enfermera_id' => $enfermeraA->id]);
    ReporteEnfermeria::factory()->count(3)->create(['enfermera_id' => $enfermeraB->id]);

    $results = scopedIndexResults('reportes-enfermeria', $enfermeraA, ReporteEnfermeria::query());

    expect($results)->toHaveCount(2);
});

it('scopes alertas-ronda via the one-hop chain rondaEnfermeria.enfermera_id', function () {
    $enfermeraA = User::factory()->create();
    $enfermeraA->assignRole('enfermera');
    $enfermeraB = User::factory()->create();
    $enfermeraB->assignRole('enfermera');

    $rondaA = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermeraA->id]);
    $rondaB = RondaEnfermeria::factory()->create(['enfermera_id' => $enfermeraB->id]);

    // `visita_habitacion_id` is nullable and irrelevant to this test; set
    // it explicitly to avoid cascading unrelated Habitacion/Residente
    // factory records (whose fake CURP values are not guaranteed unique).
    AlertaRonda::factory()->count(2)->create(['ronda_enfermeria_id' => $rondaA->id, 'visita_habitacion_id' => null]);
    AlertaRonda::factory()->count(3)->create(['ronda_enfermeria_id' => $rondaB->id, 'visita_habitacion_id' => null]);

    $results = scopedIndexResults('alertas-ronda', $enfermeraA, AlertaRonda::query());

    expect($results)->toHaveCount(2);
});
