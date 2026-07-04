<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Controllers\AlertaRondaController;
use App\Http\Requests\AlertaRondaUpdateRequest;
use App\Models\AlertaRonda;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use JMac\Testing\Traits\AdditionalAssertions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @see AlertaRondaController
 */
final class AlertaRondaControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    #[Test]
    public function index_responds_with(): void
    {
        $alertaRondas = AlertaRonda::factory()->count(3)->create();

        $response = $this->get(route('alerta-rondas.index'));

        $response->assertOk();
        $response->assertJson($alerta_rondas);
    }

    #[Test]
    public function show_responds_with(): void
    {
        $alertaRonda = AlertaRonda::factory()->create();

        $response = $this->get(route('alerta-rondas.show', $alertaRonda));

        $response->assertOk();
        $response->assertJson($alerta_ronda);
    }

    #[Test]
    public function update_uses_form_request_validation(): void
    {
        $this->assertActionUsesFormRequest(
            AlertaRondaController::class,
            'update',
            AlertaRondaUpdateRequest::class
        );
    }

    #[Test]
    public function update_responds_with(): void
    {
        $alertaRonda = AlertaRonda::factory()->create();
        $atendido = fake()->boolean();

        $response = $this->put(route('alerta-rondas.update', $alertaRonda), [
            'atendido' => $atendido,
        ]);

        $alertaRonda->refresh();

        $response->assertOk();
        $response->assertJson($alerta_ronda);

        $this->assertEquals($atendido, $alertaRonda->atendido);
    }
}
