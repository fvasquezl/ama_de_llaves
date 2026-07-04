<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\ResidenteController;
use App\Http\Requests\ResidenteStoreRequest;
use App\Http\Requests\ResidenteUpdateRequest;
use App\Models\Residente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see ResidenteController
 */
final class ResidenteControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_responds_with(): void
    {
        $residentes = Residente::factory()->count(3)->create();

        $response = $this->get(route('residentes.index'));

        $response->assertOk();
        $response->assertJson($residentes);
    }

    #[Test]
    public function store_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            ResidenteController::class,
            'store',
            ResidenteStoreRequest::class
        );
    }

    #[Test]
    public function store_saves_and_responds_with(): void
    {
        $nombre = fake()->word();
        $apellidos = fake()->word();
        $fecha_nacimiento = Carbon::parse(fake()->date());
        $curp = fake()->word();
        $diagnostico = fake()->text();
        $alergias = fake()->text();
        $contacto_emergencia = fake()->word();
        $telefono_emergencia = fake()->word();
        $foto_path = fake()->word();

        $response = $this->post(route('residentes.store'), [
            'nombre' => $nombre,
            'apellidos' => $apellidos,
            'fecha_nacimiento' => $fecha_nacimiento,
            'curp' => $curp,
            'diagnostico' => $diagnostico,
            'alergias' => $alergias,
            'contacto_emergencia' => $contacto_emergencia,
            'telefono_emergencia' => $telefono_emergencia,
            'foto_path' => $foto_path,
        ]);

        $residentes = Residente::query()
            ->where('nombre', $nombre)
            ->where('apellidos', $apellidos)
            ->where('fecha_nacimiento', $fecha_nacimiento)
            ->where('curp', $curp)
            ->where('diagnostico', $diagnostico)
            ->where('alergias', $alergias)
            ->where('contacto_emergencia', $contacto_emergencia)
            ->where('telefono_emergencia', $telefono_emergencia)
            ->where('foto_path', $foto_path)
            ->get();
        $this->assertCount(1, $residentes);
        $residente = $residentes->first();

        $response->assertNoContent(201);
    }

    #[Test]
    public function show_responds_with(): void
    {
        $residente = Residente::factory()->create();

        $response = $this->get(route('residentes.show', $residente));

        $response->assertOk();
        $response->assertJson($residente);
    }

    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            ResidenteController::class,
            'update',
            ResidenteUpdateRequest::class
        );
    }

    #[Test]
    public function update_responds_with(): void
    {
        $residente = Residente::factory()->create();
        $nombre = fake()->word();
        $apellidos = fake()->word();
        $fecha_nacimiento = Carbon::parse(fake()->date());
        $curp = fake()->word();
        $diagnostico = fake()->text();
        $alergias = fake()->text();
        $contacto_emergencia = fake()->word();
        $telefono_emergencia = fake()->word();

        $response = $this->put(route('residentes.update', $residente), [
            'nombre' => $nombre,
            'apellidos' => $apellidos,
            'fecha_nacimiento' => $fecha_nacimiento,
            'curp' => $curp,
            'diagnostico' => $diagnostico,
            'alergias' => $alergias,
            'contacto_emergencia' => $contacto_emergencia,
            'telefono_emergencia' => $telefono_emergencia,
        ]);

        $residente->refresh();

        $response->assertOk();
        $response->assertJson($residente);

        $this->assertEquals($nombre, $residente->nombre);
        $this->assertEquals($apellidos, $residente->apellidos);
        $this->assertEquals($fecha_nacimiento, $residente->fecha_nacimiento);
        $this->assertEquals($curp, $residente->curp);
        $this->assertEquals($diagnostico, $residente->diagnostico);
        $this->assertEquals($alergias, $residente->alergias);
        $this->assertEquals($contacto_emergencia, $residente->contacto_emergencia);
        $this->assertEquals($telefono_emergencia, $residente->telefono_emergencia);
    }

    #[Test]
    public function destroy_deletes_and_responds_with(): void
    {
        $residente = Residente::factory()->create();

        $response = $this->delete(route('residentes.destroy', $residente));

        $response->assertNoContent();

        $this->assertSoftDeleted($residente);
    }
}
