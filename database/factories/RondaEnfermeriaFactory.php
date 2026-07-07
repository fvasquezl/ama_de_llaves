<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RondaEnfermeriaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Programmed and real time pairs are generated to always satisfy the
     * "fin after inicio" invariant (spec-part-05 / RondaEnfermeriaRequest),
     * since JSON:API PATCH merge semantics re-validate the full current
     * attribute set on every update, not just the fields being changed.
     */
    public function definition(): array
    {
        $inicioProgramada = fake()->dateTimeBetween('06:00:00', '12:00:00');
        $finProgramada = (clone $inicioProgramada)->modify('+8 hours');

        $inicioReal = (clone $inicioProgramada)->modify('+'.fake()->numberBetween(0, 15).' minutes');
        $finReal = (clone $inicioReal)->modify('+8 hours');

        return [
            'enfermera_id' => User::factory(),
            'turno' => fake()->randomElement(['matutino', 'vespertino', 'nocturno']),
            'fecha' => fake()->date(),
            'hora_inicio_programada' => $inicioProgramada->format('H:i:s'),
            'hora_fin_programada' => $finProgramada->format('H:i:s'),
            'hora_inicio_real' => $inicioReal->format('H:i:s'),
            'hora_fin_real' => $finReal->format('H:i:s'),
            'estado' => fake()->randomElement(['pendiente', 'en_curso', 'completada', 'incompleta']),
            'notas' => fake()->text(),
        ];
    }
}
