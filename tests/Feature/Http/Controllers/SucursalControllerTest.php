<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\SucursalController;
use App\Http\Requests\SucursalStoreRequest;
use App\Http\Requests\SucursalUpdateRequest;
use App\Models\Sucursal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see SucursalController
 */
final class SucursalControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_responds_with(): void
    {
        $sucursals = Sucursal::factory()->count(3)->create();

        $response = $this->get(route('sucursals.index'));

        $response->assertOk();
        $response->assertJson($sucursales);
    }

    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            SucursalController::class,
            'store',
            SucursalStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_responds_with(): void
    {
        $nombre = fake()->word();
        $direccion = fake()->word();
        $ciudad = fake()->word();
        $telefono = fake()->word();
        $email = fake()->safeEmail();

        $response = $this->post(route('sucursals.store'), [
            'nombre' => $nombre,
            'direccion' => $direccion,
            'ciudad' => $ciudad,
            'telefono' => $telefono,
            'email' => $email,
        ]);

        $sucursals = Sucursal::query()
            ->where('nombre', $nombre)
            ->where('direccion', $direccion)
            ->where('ciudad', $ciudad)
            ->where('telefono', $telefono)
            ->where('email', $email)
            ->get();
        $this->assertCount(1, $sucursals);
        $sucursal = $sucursals->first();

        $response->assertNoContent(201);
    }

    #[Test]
    public function show_responds_with(): void
    {
        $sucursal = Sucursal::factory()->create();

        $response = $this->get(route('sucursals.show', $sucursal));

        $response->assertOk();
        $response->assertJson($sucursal);
    }

    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            SucursalController::class,
            'update',
            SucursalUpdateRequest::class
        );
    }

    #[Test]
    public function update_responds_with(): void
    {
        $sucursal = Sucursal::factory()->create();
        $nombre = fake()->word();
        $direccion = fake()->word();
        $ciudad = fake()->word();
        $telefono = fake()->word();
        $email = fake()->safeEmail();
        $activa = fake()->boolean();

        $response = $this->put(route('sucursals.update', $sucursal), [
            'nombre' => $nombre,
            'direccion' => $direccion,
            'ciudad' => $ciudad,
            'telefono' => $telefono,
            'email' => $email,
            'activa' => $activa,
        ]);

        $sucursal->refresh();

        $response->assertOk();
        $response->assertJson($sucursal);

        $this->assertEquals($nombre, $sucursal->nombre);
        $this->assertEquals($direccion, $sucursal->direccion);
        $this->assertEquals($ciudad, $sucursal->ciudad);
        $this->assertEquals($telefono, $sucursal->telefono);
        $this->assertEquals($email, $sucursal->email);
        $this->assertEquals($activa, $sucursal->activa);
    }

    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $sucursal = Sucursal::factory()->create();

        $response = $this->delete(route('sucursals.destroy', $sucursal));

        $response->assertNoContent();

        $this->assertSoftDeleted($sucursal);
    }
}
