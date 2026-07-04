<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\EstanciaController;
use App\Http\Requests\EstanciaStoreRequest;
use App\Http\Requests\EstanciaUpdateRequest;
use App\Models\Estancia;
use App\Models\Habitacion;
use App\Models\Residente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see EstanciaController
 */
final class EstanciaControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_responds_with(): void
    {
        $estancias = Estancia::factory()->count(3)->create();

        $response = $this->get(route('estancias.index'));

        $response->assertOk();
        $response->assertJson($estancias->toArray());
    }

    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            EstanciaController::class,
            'store',
            EstanciaStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_responds_with(): void
    {
        $residente = Residente::factory()->create();
        $habitacion = Habitacion::factory()->create();
        $fecha_ingreso = Carbon::parse(fake()->date());
        $notas_medicas = fake()->text();

        $response = $this->post(route('estancias.store'), [
            'residente_id' => $residente->id,
            'habitacion_id' => $habitacion->id,
            'fecha_ingreso' => $fecha_ingreso,
            'notas_medicas' => $notas_medicas,
        ]);

        $estancias = Estancia::query()
            ->where('residente_id', $residente->id)
            ->where('habitacion_id', $habitacion->id)
            ->where('fecha_ingreso', $fecha_ingreso)
            ->where('notas_medicas', $notas_medicas)
            ->get();
        $this->assertCount(1, $estancias);

        $response->assertNoContent(201);
    }

    #[Test]
    public function show_responds_with(): void
    {
        $estancia = Estancia::factory()->create();

        $response = $this->get(route('estancias.show', $estancia));

        $response->assertOk();
        $response->assertJson($estancia->toArray());
    }

    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            EstanciaController::class,
            'update',
            EstanciaUpdateRequest::class
        );
    }

    #[Test]
    public function update_responds_with(): void
    {
        $estancia = Estancia::factory()->create();
        $fecha_egreso = Carbon::parse(fake()->date());
        $estado = fake()->randomElement(['activa', 'alta', 'traslado', 'fallecimiento']);
        $notas_medicas = fake()->text();

        $response = $this->put(route('estancias.update', $estancia), [
            'fecha_egreso' => $fecha_egreso,
            'estado' => $estado,
            'notas_medicas' => $notas_medicas,
        ]);

        $estancia->refresh();

        $response->assertOk();
        $response->assertJson($estancia->toArray());

        $this->assertEquals($fecha_egreso, $estancia->fecha_egreso);
        $this->assertEquals($estado, $estancia->estado);
        $this->assertEquals($notas_medicas, $estancia->notas_medicas);
    }
}
