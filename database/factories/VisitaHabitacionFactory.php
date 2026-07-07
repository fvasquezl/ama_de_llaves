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
        // NFC fields are generated consistently (spec-part-08's
        // consistency rule, enforced by VisitaHabitacionRequest): a
        // non-null `nfc_escaneado_at` only ever appears alongside
        // `nfc_verificado = true`.
        $nfcVerificado = fake()->boolean();

        return [
            'ronda_enfermeria_id' => RondaEnfermeria::factory(),
            'habitacion_id' => Habitacion::factory(),
            'residente_id' => Residente::factory(),
            'hora_programada' => fake()->time(),
            'nfc_verificado' => $nfcVerificado,
            'nfc_escaneado_at' => $nfcVerificado ? fake()->dateTime() : null,
            'estado' => fake()->randomElement(['pendiente', 'en_progreso', 'completada', 'omitida']),
            'notas' => fake()->text(),
        ];
    }
}
