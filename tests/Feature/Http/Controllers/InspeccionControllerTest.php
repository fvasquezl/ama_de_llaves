<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\InspeccionController;
use App\Http\Requests\InspeccionStoreRequest;
use App\Models\Inspeccion;
use App\Models\TareaLimpieza;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see InspeccionController
 */
final class InspeccionControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_responds_with(): void
    {
        $inspeccions = Inspeccion::factory()->count(3)->create();

        $response = $this->get(route('inspeccions.index'));

        $response->assertOk();
        $response->assertJson($inspecciones);
    }

    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            InspeccionController::class,
            'store',
            InspeccionStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_responds_with(): void
    {
        $tarea_limpieza = TareaLimpieza::factory()->create();
        $resultado = fake()->randomElement(/** enum_attributes **/);
        $puntaje = fake()->randomNumber();
        $notas = fake()->text();

        $response = $this->post(route('inspeccions.store'), [
            'tarea_limpieza_id' => $tarea_limpieza->id,
            'resultado' => $resultado,
            'puntaje' => $puntaje,
            'notas' => $notas,
        ]);

        $inspeccions = Inspeccion::query()
            ->where('tarea_limpieza_id', $tarea_limpieza->id)
            ->where('resultado', $resultado)
            ->where('puntaje', $puntaje)
            ->where('notas', $notas)
            ->get();
        $this->assertCount(1, $inspeccions);
        $inspeccion = $inspeccions->first();

        $response->assertNoContent(201);
    }

    #[Test]
    public function show_responds_with(): void
    {
        $inspeccion = Inspeccion::factory()->create();

        $response = $this->get(route('inspeccions.show', $inspeccion));

        $response->assertOk();
        $response->assertJson($inspeccion);
    }
}
