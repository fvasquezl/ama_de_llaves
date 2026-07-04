<?php

namespace Database\Factories;

use App\Models\RondaEnfermeria;
use App\Models\User;
use App\Models\VisitaHabitacion;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlertaRondaFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'ronda_enfermeria_id' => RondaEnfermeria::factory(),
            'visita_habitacion_id' => VisitaHabitacion::factory(),
            'tipo' => fake()->randomElement(['visita_tardia', 'visita_omitida', 'turno_incompleto']),
            'atendido' => fake()->boolean(),
            'atendido_por_id' => User::factory(),
        ];
    }
}
