<?php

namespace Database\Factories;

use App\Models\TareaLimpieza;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChecklistItemFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'tarea_limpieza_id' => TareaLimpieza::factory(),
            'descripcion' => fake()->word(),
            'completado' => fake()->boolean(),
            'orden' => fake()->randomNumber(),
        ];
    }
}
