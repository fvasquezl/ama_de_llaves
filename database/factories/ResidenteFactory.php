<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
            'curp' => Str::upper(Str::random(18)),
            'diagnostico' => fake()->text(),
            'alergias' => fake()->text(),
            'contacto_emergencia' => fake()->word(),
            'telefono_emergencia' => fake()->word(),
            'foto_path' => fake()->word(),
        ];
    }
}
