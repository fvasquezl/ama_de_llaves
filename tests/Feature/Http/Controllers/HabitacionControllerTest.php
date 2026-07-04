<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\HabitacionController;
use App\Http\Requests\HabitacionStoreRequest;
use App\Http\Requests\HabitacionUpdateRequest;
use App\Models\Habitacion;
use App\Models\Sucursal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see HabitacionController
 */
final class HabitacionControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_responds_with(): void
    {
        $habitacions = Habitacion::factory()->count(3)->create();

        $response = $this->get(route('habitacions.index'));

        $response->assertOk();
        $response->assertJson($habitaciones);
    }

    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            HabitacionController::class,
            'store',
            HabitacionStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_responds_with(): void
    {
        $sucursal = Sucursal::factory()->create();
        $numero = fake()->word();
        $tipo = fake()->randomElement(/** enum_attributes **/);
        $piso = fake()->randomNumber();
        $capacidad = fake()->randomNumber();
        $nfc_tag_uid = fake()->word();
        $notas = fake()->text();

        $response = $this->post(route('habitacions.store'), [
            'sucursal_id' => $sucursal->id,
            'numero' => $numero,
            'tipo' => $tipo,
            'piso' => $piso,
            'capacidad' => $capacidad,
            'nfc_tag_uid' => $nfc_tag_uid,
            'notas' => $notas,
        ]);

        $habitacions = Habitacion::query()
            ->where('sucursal_id', $sucursal->id)
            ->where('numero', $numero)
            ->where('tipo', $tipo)
            ->where('piso', $piso)
            ->where('capacidad', $capacidad)
            ->where('nfc_tag_uid', $nfc_tag_uid)
            ->where('notas', $notas)
            ->get();
        $this->assertCount(1, $habitacions);
        $habitacion = $habitacions->first();

        $response->assertNoContent(201);
    }

    #[Test]
    public function show_responds_with(): void
    {
        $habitacion = Habitacion::factory()->create();

        $response = $this->get(route('habitacions.show', $habitacion));

        $response->assertOk();
        $response->assertJson($habitacion);
    }

    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            HabitacionController::class,
            'update',
            HabitacionUpdateRequest::class
        );
    }

    #[Test]
    public function update_responds_with(): void
    {
        $habitacion = Habitacion::factory()->create();
        $numero = fake()->word();
        $tipo = fake()->randomElement(/** enum_attributes **/);
        $piso = fake()->randomNumber();
        $capacidad = fake()->randomNumber();
        $estado = fake()->randomElement(/** enum_attributes **/);
        $nfc_tag_uid = fake()->word();
        $notas = fake()->text();

        $response = $this->put(route('habitacions.update', $habitacion), [
            'numero' => $numero,
            'tipo' => $tipo,
            'piso' => $piso,
            'capacidad' => $capacidad,
            'estado' => $estado,
            'nfc_tag_uid' => $nfc_tag_uid,
            'notas' => $notas,
        ]);

        $habitacion->refresh();

        $response->assertOk();
        $response->assertJson($habitacion);

        $this->assertEquals($numero, $habitacion->numero);
        $this->assertEquals($tipo, $habitacion->tipo);
        $this->assertEquals($piso, $habitacion->piso);
        $this->assertEquals($capacidad, $habitacion->capacidad);
        $this->assertEquals($estado, $habitacion->estado);
        $this->assertEquals($nfc_tag_uid, $habitacion->nfc_tag_uid);
        $this->assertEquals($notas, $habitacion->notas);
    }

    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $habitacion = Habitacion::factory()->create();

        $response = $this->delete(route('habitacions.destroy', $habitacion));

        $response->assertNoContent();

        $this->assertSoftDeleted($habitacion);
    }
}
