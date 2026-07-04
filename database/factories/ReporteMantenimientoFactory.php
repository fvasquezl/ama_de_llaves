<?php

namespace Database\Factories;

use App\Models\Habitacion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReporteMantenimientoFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'habitacion_id' => Habitacion::factory(),
            'reportado_por_id' => User::factory(),
            'descripcion' => fake()->text(),
            'prioridad' => fake()->randomElement(['baja', 'normal', 'alta', 'urgente']),
            'estado' => fake()->randomElement(['pendiente', 'en_proceso', 'resuelto']),
            'foto_path' => fake()->word(),
            'notas_resolucion' => fake()->text(),
        ];
    }
}
