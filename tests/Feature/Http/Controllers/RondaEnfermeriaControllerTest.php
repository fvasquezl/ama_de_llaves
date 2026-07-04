<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\RondaEnfermeriaController;
use App\Http\Requests\RondaEnfermeriaStoreRequest;
use App\Http\Requests\RondaEnfermeriaUpdateRequest;
use App\Models\RondaEnfermeria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see RondaEnfermeriaController
 */
final class RondaEnfermeriaControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_responds_with(): void
    {
        $rondaEnfermeria = RondaEnfermeria::factory()->count(3)->create();

        $response = $this->get(route('ronda-enfermeria.index'));

        $response->assertOk();
        $response->assertJson($ronda_enformerias);
    }

    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            RondaEnfermeriaController::class,
            'store',
            RondaEnfermeriaStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_responds_with(): void
    {
        $enfermera = User::factory()->create();
        $turno = fake()->randomElement(/** enum_attributes **/);
        $fecha = Carbon::parse(fake()->date());
        $hora_inicio_programada = fake()->time();
        $hora_fin_programada = fake()->time();

        $response = $this->post(route('ronda-enfermeria.store'), [
            'enfermera_id' => $enfermera->id,
            'turno' => $turno,
            'fecha' => $fecha,
            'hora_inicio_programada' => $hora_inicio_programada,
            'hora_fin_programada' => $hora_fin_programada,
        ]);

        $rondaEnfermeria = RondaEnfermeria::query()
            ->where('enfermera_id', $enfermera->id)
            ->where('turno', $turno)
            ->where('fecha', $fecha)
            ->where('hora_inicio_programada', $hora_inicio_programada)
            ->where('hora_fin_programada', $hora_fin_programada)
            ->get();
        $this->assertCount(1, $rondaEnfermeria);
        $rondaEnfermerium = $rondaEnfermeria->first();

        $response->assertNoContent(201);
    }

    #[Test]
    public function show_responds_with(): void
    {
        $rondaEnfermerium = RondaEnfermeria::factory()->create();

        $response = $this->get(route('ronda-enfermeria.show', $rondaEnfermerium));

        $response->assertOk();
        $response->assertJson($ronda_enfermeria);
    }

    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            RondaEnfermeriaController::class,
            'update',
            RondaEnfermeriaUpdateRequest::class
        );
    }

    #[Test]
    public function update_responds_with(): void
    {
        $rondaEnfermerium = RondaEnfermeria::factory()->create();
        $hora_inicio_real = fake()->time();
        $hora_fin_real = fake()->time();
        $estado = fake()->randomElement(/** enum_attributes **/);
        $notas = fake()->text();

        $response = $this->put(route('ronda-enfermeria.update', $rondaEnfermerium), [
            'hora_inicio_real' => $hora_inicio_real,
            'hora_fin_real' => $hora_fin_real,
            'estado' => $estado,
            'notas' => $notas,
        ]);

        $rondaEnfermerium->refresh();

        $response->assertOk();
        $response->assertJson($ronda_enfermeria);

        $this->assertEquals($hora_inicio_real, $rondaEnfermerium->hora_inicio_real);
        $this->assertEquals($hora_fin_real, $rondaEnfermerium->hora_fin_real);
        $this->assertEquals($estado, $rondaEnfermerium->estado);
        $this->assertEquals($notas, $rondaEnfermerium->notas);
    }

    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $rondaEnfermerium = RondaEnfermeria::factory()->create();

        $response = $this->delete(route('ronda-enfermeria.destroy', $rondaEnfermerium));

        $response->assertNoContent();

        $this->assertModelMissing($rondaEnfermerium);
    }
}
