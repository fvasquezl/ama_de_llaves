<?php

namespace Database\Factories;

use App\Models\Sucursal;
use Illuminate\Database\Eloquent\Factories\Factory;

class HabitacionFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'sucursal_id' => Sucursal::factory(),
            'numero' => fake()->word(),
            'tipo' => fake()->randomElement(['individual', 'doble', 'suite']),
            'piso' => fake()->randomNumber(),
            'capacidad' => fake()->randomNumber(),
            'estado' => fake()->randomElement(['disponible', 'ocupada', 'sucia', 'en_limpieza', 'limpia', 'inspeccionada', 'fuera_de_servicio']),
            'nfc_tag_uid' => fake()->word(),
            'notas' => fake()->text(),
        ];
    }
}
