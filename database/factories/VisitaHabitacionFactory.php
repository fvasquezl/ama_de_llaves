<?php

namespace Database\Factories;

use App\Models\Habitacion;
use App\Models\Residente;
use App\Models\RondaEnfermeria;
use Illuminate\Database\Eloquent\Factories\Factory;

class VisitaHabitacionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ronda_enfermeria_id' => RondaEnfermeria::factory(),
            'habitacion_id' => Habitacion::factory(),
            'residente_id' => Residente::factory(),
            'hora_programada' => fake()->time(),
            'nfc_verificado' => fake()->boolean(),
            'nfc_escaneado_at' => fake()->dateTime(),
            'estado' => fake()->randomElement(['pendiente', 'en_progreso', 'completada', 'omitida']),
            'notas' => fake()->text(),
        ];
    }
}
