<?php

namespace Database\Factories;

use App\Models\Curso;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Grupo>
 */
class GrupoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'curso_id' => Curso::factory(),
            'numeroCiclo' => 2,
            'ano' => 2026,
            'numeroGrupo' => fake()->numberBetween(1, 9),
            'abierto' => false,
        ];
    }
}
