<?php

namespace Database\Factories;

use App\Models\Habitacion;
use App\Models\Residente;
use Illuminate\Database\Eloquent\Factories\Factory;

class EstanciaFactory extends Factory
{
    public function definition(): array
    {
        $fechaIngreso = fake()->dateTimeBetween('-2 years', 'now');

        return [
            'residente_id' => Residente::factory(),
            'habitacion_id' => Habitacion::factory(),
            'fecha_ingreso' => $fechaIngreso->format('Y-m-d'),
            'fecha_egreso' => fake()->optional()->dateTimeBetween($fechaIngreso, 'now')?->format('Y-m-d'),
            'estado' => fake()->randomElement(['activa', 'alta', 'traslado', 'fallecimiento']),
            'notas_medicas' => fake()->text(),
        ];
    }
}
