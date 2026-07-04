<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RondaEnfermeriaFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'enfermera_id' => User::factory(),
            'turno' => fake()->randomElement(['matutino', 'vespertino', 'nocturno']),
            'fecha' => fake()->date(),
            'hora_inicio_programada' => fake()->time(),
            'hora_fin_programada' => fake()->time(),
            'hora_inicio_real' => fake()->time(),
            'hora_fin_real' => fake()->time(),
            'estado' => fake()->randomElement(['pendiente', 'en_curso', 'completada', 'incompleta']),
            'notas' => fake()->text(),
            'user_id' => User::factory(),
        ];
    }
}
