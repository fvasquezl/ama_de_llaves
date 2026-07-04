<?php

namespace Database\Factories;

use App\Models\VisitaHabitacion;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChecklistEnfermeriaItemFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'visita_habitacion_id' => VisitaHabitacion::factory(),
            'descripcion' => fake()->word(),
            'completado' => fake()->boolean(),
            'valor' => fake()->word(),
            'orden' => fake()->randomNumber(),
        ];
    }
}
