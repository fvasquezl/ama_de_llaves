<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SucursalFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->word(),
            'direccion' => fake()->word(),
            'ciudad' => fake()->word(),
            'telefono' => fake()->word(),
            'email' => fake()->safeEmail(),
            'activa' => fake()->boolean(),
        ];
    }
}
