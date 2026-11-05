<?php

namespace Database\Factories;

use App\Models\Curso;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Curso>
 */
class CursoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sigla' => 'IF9' . fake()->unique()->numerify('###'),
            'nombreCurso' => fake()->unique()->sentence(3),
            'creditos' => fake()->numberBetween(1, 4),
        ];
    }
}
