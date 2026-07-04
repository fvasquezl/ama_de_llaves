<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ResidenteFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->word(),
            'apellidos' => fake()->word(),
            'fecha_nacimiento' => fake()->date(),
            'curp' => fake()->word(),
            'diagnostico' => fake()->text(),
            'alergias' => fake()->text(),
            'contacto_emergencia' => fake()->word(),
            'telefono_emergencia' => fake()->word(),
            'foto_path' => fake()->word(),
        ];
    }
}
