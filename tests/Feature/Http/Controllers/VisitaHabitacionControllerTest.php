<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\VisitaHabitacionController;
use App\Http\Requests\VisitaHabitacionStoreRequest;
use App\Http\Requests\VisitaHabitacionUpdateRequest;
use App\Models\Habitacion;
use App\Models\Residente;
use App\Models\RondaEnfermeria;
use App\Models\VisitaHabitacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see VisitaHabitacionController
 */
final class VisitaHabitacionControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_responds_with(): void
    {
        $visitaHabitaciones = VisitaHabitacion::factory()->count(3)->create();

        $response = $this->get(route('visita-habitacions.index'));

        $response->assertOk();
        $response->assertJson($visitaHabitaciones->toArray());
    }

    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            VisitaHabitacionController::class,
            'store',
            VisitaHabitacionStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_responds_with(): void
    {
        $rondaEnfermeria = RondaEnfermeria::factory()->create();
        $habitacion = Habitacion::factory()->create();
        $residente = Residente::factory()->create();
        $hora_programada = fake()->time();

        $response = $this->post(route('visita-habitacions.store'), [
            'ronda_enfermeria_id' => $rondaEnfermeria->id,
            'habitacion_id' => $habitacion->id,
            'residente_id' => $residente->id,
            'hora_programada' => $hora_programada,
        ]);

        $visitaHabitaciones = VisitaHabitacion::query()
            ->where('ronda_enfermeria_id', $rondaEnfermeria->id)
            ->where('habitacion_id', $habitacion->id)
            ->where('residente_id', $residente->id)
            ->where('hora_programada', $hora_programada)
            ->get();
        $this->assertCount(1, $visitaHabitaciones);

        $response->assertNoContent(201);
    }

    #[Test]
    public function show_responds_with(): void
    {
        $visitaHabitacion = VisitaHabitacion::factory()->create();

        $response = $this->get(route('visita-habitacions.show', $visitaHabitacion));

        $response->assertOk();
        $response->assertJson($visitaHabitacion->toArray());
    }

    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            VisitaHabitacionController::class,
            'update',
            VisitaHabitacionUpdateRequest::class
        );
    }

    #[Test]
    public function update_responds_with(): void
    {
        $visitaHabitacion = VisitaHabitacion::factory()->create();
        $estado = fake()->randomElement(['pendiente', 'en_progreso', 'completada', 'omitida']);
        $notas = fake()->text();

        $response = $this->put(route('visita-habitacions.update', $visitaHabitacion), [
            'estado' => $estado,
            'notas' => $notas,
        ]);

        $visitaHabitacion->refresh();

        $response->assertOk();
        $response->assertJson($visitaHabitacion->toArray());

        $this->assertEquals($estado, $visitaHabitacion->estado);
        $this->assertEquals($notas, $visitaHabitacion->notas);
    }
}
