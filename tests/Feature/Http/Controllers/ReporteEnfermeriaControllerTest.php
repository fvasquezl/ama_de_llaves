<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\ReporteEnfermeriaController;
use App\Http\Requests\ReporteEnfermeriaStoreRequest;
use App\Http\Requests\ReporteEnfermeriaUpdateRequest;
use App\Models\ReporteEnfermeria;
use App\Models\RondaEnfermeria;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see ReporteEnfermeriaController
 */
final class ReporteEnfermeriaControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_responds_with(): void
    {
        $reporteEnfermeria = ReporteEnfermeria::factory()->count(3)->create();

        $response = $this->get(route('reporte-enfermeria.index'));

        $response->assertOk();
        $response->assertJson($reporte_enformerias);
    }

    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            ReporteEnfermeriaController::class,
            'store',
            ReporteEnfermeriaStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_responds_with(): void
    {
        $ronda_enfermeria = RondaEnfermeria::factory()->create();
        $incidencias = fake()->text();
        $observaciones = fake()->text();

        $response = $this->post(route('reporte-enfermeria.store'), [
            'ronda_enfermeria_id' => $ronda_enfermeria->id,
            'incidencias' => $incidencias,
            'observaciones' => $observaciones,
        ]);

        $reporteEnfermeria = ReporteEnfermeria::query()
            ->where('ronda_enfermeria_id', $ronda_enfermeria->id)
            ->where('incidencias', $incidencias)
            ->where('observaciones', $observaciones)
            ->get();
        $this->assertCount(1, $reporteEnfermeria);
        $reporteEnfermerium = $reporteEnfermeria->first();

        $response->assertNoContent(201);
    }

    #[Test]
    public function show_responds_with(): void
    {
        $reporteEnfermerium = ReporteEnfermeria::factory()->create();

        $response = $this->get(route('reporte-enfermeria.show', $reporteEnfermerium));

        $response->assertOk();
        $response->assertJson($reporte_enfermeria);
    }

    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            ReporteEnfermeriaController::class,
            'update',
            ReporteEnfermeriaUpdateRequest::class
        );
    }

    #[Test]
    public function update_responds_with(): void
    {
        $reporteEnfermerium = ReporteEnfermeria::factory()->create();
        $incidencias = fake()->text();
        $observaciones = fake()->text();
        $estado = fake()->randomElement(/** enum_attributes **/);

        $response = $this->put(route('reporte-enfermeria.update', $reporteEnfermerium), [
            'incidencias' => $incidencias,
            'observaciones' => $observaciones,
            'estado' => $estado,
        ]);

        $reporteEnfermerium->refresh();

        $response->assertOk();
        $response->assertJson($reporte_enfermeria);

        $this->assertEquals($incidencias, $reporteEnfermerium->incidencias);
        $this->assertEquals($observaciones, $reporteEnfermerium->observaciones);
        $this->assertEquals($estado, $reporteEnfermerium->estado);
    }
}
