<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\ReporteMantenimientoController;
use App\Http\Requests\ReporteMantenimientoStoreRequest;
use App\Http\Requests\ReporteMantenimientoUpdateRequest;
use App\Models\Habitacion;
use App\Models\ReporteMantenimiento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see ReporteMantenimientoController
 */
final class ReporteMantenimientoControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_responds_with(): void
    {
        $reporteMantenimientos = ReporteMantenimiento::factory()->count(3)->create();

        $response = $this->get(route('reporte-mantenimientos.index'));

        $response->assertOk();
        $response->assertJson($reporte_mantenimientos);
    }

    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            ReporteMantenimientoController::class,
            'store',
            ReporteMantenimientoStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_responds_with(): void
    {
        $habitacion = Habitacion::factory()->create();
        $descripcion = fake()->text();
        $prioridad = fake()->randomElement(/** enum_attributes **/);
        $foto_path = fake()->word();

        $response = $this->post(route('reporte-mantenimientos.store'), [
            'habitacion_id' => $habitacion->id,
            'descripcion' => $descripcion,
            'prioridad' => $prioridad,
            'foto_path' => $foto_path,
        ]);

        $reporteMantenimientos = ReporteMantenimiento::query()
            ->where('habitacion_id', $habitacion->id)
            ->where('descripcion', $descripcion)
            ->where('prioridad', $prioridad)
            ->where('foto_path', $foto_path)
            ->get();
        $this->assertCount(1, $reporteMantenimientos);
        $reporteMantenimiento = $reporteMantenimientos->first();

        $response->assertNoContent(201);
    }

    #[Test]
    public function show_responds_with(): void
    {
        $reporteMantenimiento = ReporteMantenimiento::factory()->create();

        $response = $this->get(route('reporte-mantenimientos.show', $reporteMantenimiento));

        $response->assertOk();
        $response->assertJson($reporte_mantenimiento);
    }

    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            ReporteMantenimientoController::class,
            'update',
            ReporteMantenimientoUpdateRequest::class
        );
    }

    #[Test]
    public function update_responds_with(): void
    {
        $reporteMantenimiento = ReporteMantenimiento::factory()->create();
        $estado = fake()->randomElement(/** enum_attributes **/);
        $notas_resolucion = fake()->text();
        $prioridad = fake()->randomElement(/** enum_attributes **/);

        $response = $this->put(route('reporte-mantenimientos.update', $reporteMantenimiento), [
            'estado' => $estado,
            'notas_resolucion' => $notas_resolucion,
            'prioridad' => $prioridad,
        ]);

        $reporteMantenimiento->refresh();

        $response->assertOk();
        $response->assertJson($reporte_mantenimiento);

        $this->assertEquals($estado, $reporteMantenimiento->estado);
        $this->assertEquals($notas_resolucion, $reporteMantenimiento->notas_resolucion);
        $this->assertEquals($prioridad, $reporteMantenimiento->prioridad);
    }

    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $reporteMantenimiento = ReporteMantenimiento::factory()->create();

        $response = $this->delete(route('reporte-mantenimientos.destroy', $reporteMantenimiento));

        $response->assertNoContent();

        $this->assertModelMissing($reporteMantenimiento);
    }
}
