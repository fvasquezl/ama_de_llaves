<?php

namespace Database\Factories;

use App\Models\Habitacion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TareaLimpiezaFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'habitacion_id' => Habitacion::factory(),
            'camarera_id' => User::factory(),
            'supervisora_id' => User::factory(),
            'tipo' => fake()->randomElement(['salida', 'estancia', 'profunda', 'llegada']),
            'prioridad' => fake()->randomElement(['baja', 'normal', 'alta', 'urgente']),
            'estado' => fake()->randomElement(['pendiente', 'en_progreso', 'completada', 'inspeccionada', 'rechazada']),
            'fecha_programada' => fake()->date(),
            'hora_inicio' => fake()->time(),
            'hora_fin' => fake()->time(),
            'notas' => fake()->text(),
        ];
    }
}
