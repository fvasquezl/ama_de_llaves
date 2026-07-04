<?php

namespace Database\Factories;

use App\Models\RondaEnfermeria;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReporteEnfermeriaFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'ronda_enfermeria_id' => RondaEnfermeria::factory(),
            'enfermera_id' => User::factory(),
            'incidencias' => fake()->text(),
            'observaciones' => fake()->text(),
            'firmado_at' => fake()->dateTime(),
            'estado' => fake()->randomElement(['borrador', 'firmado']),
        ];
    }
}
