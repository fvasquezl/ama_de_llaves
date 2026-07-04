<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\ChecklistItemController;
use App\Http\Requests\ChecklistItemStoreRequest;
use App\Http\Requests\ChecklistItemUpdateRequest;
use App\Models\ChecklistItem;
use App\Models\TareaLimpieza;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see ChecklistItemController
 */
final class ChecklistItemControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_responds_with(): void
    {
        $checklistItems = ChecklistItem::factory()->count(3)->create();

        $response = $this->get(route('checklist-items.index'));

        $response->assertOk();
        $response->assertJson($checklist_items);
    }

    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            ChecklistItemController::class,
            'store',
            ChecklistItemStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_responds_with(): void
    {
        $tarea_limpieza = TareaLimpieza::factory()->create();
        $descripcion = fake()->word();
        $orden = fake()->randomNumber();

        $response = $this->post(route('checklist-items.store'), [
            'tarea_limpieza_id' => $tarea_limpieza->id,
            'descripcion' => $descripcion,
            'orden' => $orden,
        ]);

        $checklistItems = ChecklistItem::query()
            ->where('tarea_limpieza_id', $tarea_limpieza->id)
            ->where('descripcion', $descripcion)
            ->where('orden', $orden)
            ->get();
        $this->assertCount(1, $checklistItems);
        $checklistItem = $checklistItems->first();

        $response->assertNoContent(201);
    }

    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            ChecklistItemController::class,
            'update',
            ChecklistItemUpdateRequest::class
        );
    }

    #[Test]
    public function update_responds_with(): void
    {
        $checklistItem = ChecklistItem::factory()->create();
        $completado = fake()->boolean();
        $descripcion = fake()->word();
        $orden = fake()->randomNumber();

        $response = $this->put(route('checklist-items.update', $checklistItem), [
            'completado' => $completado,
            'descripcion' => $descripcion,
            'orden' => $orden,
        ]);

        $checklistItem->refresh();

        $response->assertOk();
        $response->assertJson($checklist_item);

        $this->assertEquals($completado, $checklistItem->completado);
        $this->assertEquals($descripcion, $checklistItem->descripcion);
        $this->assertEquals($orden, $checklistItem->orden);
    }

    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $checklistItem = ChecklistItem::factory()->create();

        $response = $this->delete(route('checklist-items.destroy', $checklistItem));

        $response->assertNoContent();

        $this->assertModelMissing($checklistItem);
    }
}
