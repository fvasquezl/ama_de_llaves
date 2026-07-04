<?php

namespace Database\Factories;

use App\Models\Habitacion;
use App\Models\Residente;
use Illuminate\Database\Eloquent\Factories\Factory;

class EstanciaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'residente_id' => Residente::factory(),
            'habitacion_id' => Habitacion::factory(),
            'fecha_ingreso' => fake()->date(),
            'fecha_egreso' => fake()->date(),
            'estado' => fake()->randomElement(['activa', 'alta', 'traslado', 'fallecimiento']),
            'notas_medicas' => fake()->text(),
        ];
    }
}
