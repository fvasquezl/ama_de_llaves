<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\TareaLimpiezaController;
use App\Http\Requests\TareaLimpiezaStoreRequest;
use App\Http\Requests\TareaLimpiezaUpdateRequest;
use App\Models\Habitacion;
use App\Models\TareaLimpieza;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see TareaLimpiezaController
 */
final class TareaLimpiezaControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_responds_with(): void
    {
        $tareaLimpiezas = TareaLimpieza::factory()->count(3)->create();

        $response = $this->get(route('tarea-limpiezas.index'));

        $response->assertOk();
        $response->assertJson($tarea_limpiezas);
    }

    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            TareaLimpiezaController::class,
            'store',
            TareaLimpiezaStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_responds_with(): void
    {
        $habitacion = Habitacion::factory()->create();
        $camarera = User::factory()->create();
        $supervisora = User::factory()->create();
        $tipo = fake()->randomElement(/** enum_attributes **/);
        $prioridad = fake()->randomElement(/** enum_attributes **/);
        $fecha_programada = Carbon::parse(fake()->date());
        $notas = fake()->text();

        $response = $this->post(route('tarea-limpiezas.store'), [
            'habitacion_id' => $habitacion->id,
            'camarera_id' => $camarera->id,
            'supervisora_id' => $supervisora->id,
            'tipo' => $tipo,
            'prioridad' => $prioridad,
            'fecha_programada' => $fecha_programada,
            'notas' => $notas,
        ]);

        $tareaLimpiezas = TareaLimpieza::query()
            ->where('habitacion_id', $habitacion->id)
            ->where('camarera_id', $camarera->id)
            ->where('supervisora_id', $supervisora->id)
            ->where('tipo', $tipo)
            ->where('prioridad', $prioridad)
            ->where('fecha_programada', $fecha_programada)
            ->where('notas', $notas)
            ->get();
        $this->assertCount(1, $tareaLimpiezas);
        $tareaLimpieza = $tareaLimpiezas->first();

        $response->assertNoContent(201);
    }

    #[Test]
    public function show_responds_with(): void
    {
        $tareaLimpieza = TareaLimpieza::factory()->create();

        $response = $this->get(route('tarea-limpiezas.show', $tareaLimpieza));

        $response->assertOk();
        $response->assertJson($tarea_limpieza);
    }

    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            TareaLimpiezaController::class,
            'update',
            TareaLimpiezaUpdateRequest::class
        );
    }

    #[Test]
    public function update_responds_with(): void
    {
        $tareaLimpieza = TareaLimpieza::factory()->create();
        $camarera = User::factory()->create();
        $supervisora = User::factory()->create();
        $prioridad = fake()->randomElement(/** enum_attributes **/);
        $estado = fake()->randomElement(/** enum_attributes **/);
        $fecha_programada = Carbon::parse(fake()->date());
        $hora_inicio = fake()->time();
        $hora_fin = fake()->time();
        $notas = fake()->text();

        $response = $this->put(route('tarea-limpiezas.update', $tareaLimpieza), [
            'camarera_id' => $camarera->id,
            'supervisora_id' => $supervisora->id,
            'prioridad' => $prioridad,
            'estado' => $estado,
            'fecha_programada' => $fecha_programada,
            'hora_inicio' => $hora_inicio,
            'hora_fin' => $hora_fin,
            'notas' => $notas,
        ]);

        $tareaLimpieza->refresh();

        $response->assertOk();
        $response->assertJson($tarea_limpieza);

        $this->assertEquals($camarera->id, $tareaLimpieza->camarera_id);
        $this->assertEquals($supervisora->id, $tareaLimpieza->supervisora_id);
        $this->assertEquals($prioridad, $tareaLimpieza->prioridad);
        $this->assertEquals($estado, $tareaLimpieza->estado);
        $this->assertEquals($fecha_programada, $tareaLimpieza->fecha_programada);
        $this->assertEquals($hora_inicio, $tareaLimpieza->hora_inicio);
        $this->assertEquals($hora_fin, $tareaLimpieza->hora_fin);
        $this->assertEquals($notas, $tareaLimpieza->notas);
    }

    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $tareaLimpieza = TareaLimpieza::factory()->create();

        $response = $this->delete(route('tarea-limpiezas.destroy', $tareaLimpieza));

        $response->assertNoContent();

        $this->assertModelMissing($tareaLimpieza);
    }
}
