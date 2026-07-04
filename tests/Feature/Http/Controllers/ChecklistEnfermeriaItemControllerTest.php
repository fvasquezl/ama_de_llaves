<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\ChecklistEnfermeriaItemController;
use App\Http\Requests\ChecklistEnfermeriaItemStoreRequest;
use App\Http\Requests\ChecklistEnfermeriaItemUpdateRequest;
use App\Models\ChecklistEnfermeriaItem;
use App\Models\VisitaHabitacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see ChecklistEnfermeriaItemController
 */
final class ChecklistEnfermeriaItemControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_responds_with(): void
    {
        $checklistEnfermeriaItems = ChecklistEnfermeriaItem::factory()->count(3)->create();

        $response = $this->get(route('checklist-enfermeria-items.index'));

        $response->assertOk();
        $response->assertJson($checklist_enfermeria_items);
    }

    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            ChecklistEnfermeriaItemController::class,
            'store',
            ChecklistEnfermeriaItemStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_responds_with(): void
    {
        $visita_habitacion = VisitaHabitacion::factory()->create();
        $descripcion = fake()->word();
        $orden = fake()->randomNumber();

        $response = $this->post(route('checklist-enfermeria-items.store'), [
            'visita_habitacion_id' => $visita_habitacion->id,
            'descripcion' => $descripcion,
            'orden' => $orden,
        ]);

        $checklistEnfermeriaItems = ChecklistEnfermeriaItem::query()
            ->where('visita_habitacion_id', $visita_habitacion->id)
            ->where('descripcion', $descripcion)
            ->where('orden', $orden)
            ->get();
        $this->assertCount(1, $checklistEnfermeriaItems);
        $checklistEnfermeriaItem = $checklistEnfermeriaItems->first();

        $response->assertNoContent(201);
    }

    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            ChecklistEnfermeriaItemController::class,
            'update',
            ChecklistEnfermeriaItemUpdateRequest::class
        );
    }

    #[Test]
    public function update_responds_with(): void
    {
        $checklistEnfermeriaItem = ChecklistEnfermeriaItem::factory()->create();
        $completado = fake()->boolean();
        $valor = fake()->word();
        $descripcion = fake()->word();

        $response = $this->put(route('checklist-enfermeria-items.update', $checklistEnfermeriaItem), [
            'completado' => $completado,
            'valor' => $valor,
            'descripcion' => $descripcion,
        ]);

        $checklistEnfermeriaItem->refresh();

        $response->assertOk();
        $response->assertJson($checklist_enfermeria_item);

        $this->assertEquals($completado, $checklistEnfermeriaItem->completado);
        $this->assertEquals($valor, $checklistEnfermeriaItem->valor);
        $this->assertEquals($descripcion, $checklistEnfermeriaItem->descripcion);
    }

    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $checklistEnfermeriaItem = ChecklistEnfermeriaItem::factory()->create();

        $response = $this->delete(route('checklist-enfermeria-items.destroy', $checklistEnfermeriaItem));

        $response->assertNoContent();

        $this->assertModelMissing($checklistEnfermeriaItem);
    }
}
