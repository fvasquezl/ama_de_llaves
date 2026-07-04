<?php

namespace Database\Factories;

use App\Models\TareaLimpieza;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InspeccionFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'tarea_limpieza_id' => TareaLimpieza::factory(),
            'supervisora_id' => User::factory(),
            'resultado' => fake()->randomElement(['aprobada', 'rechazada']),
            'puntaje' => fake()->randomNumber(),
            'notas' => fake()->text(),
        ];
    }
}
