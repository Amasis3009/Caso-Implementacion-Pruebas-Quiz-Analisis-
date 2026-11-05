<?php

namespace Database\Factories;

use App\Models\Docente;
use Illuminate\Database\Eloquent\Factories\Factory;

class EstudianteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'carne' => 'C' . fake()->unique()->numberBetween(20000, 99999),
            'nombre' => fake()->firstName(),
            'apellidos' => fake()->lastName(),
            'consejero_id' => Docente::factory(),
        ];
    }
}
