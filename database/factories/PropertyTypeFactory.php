<?php

namespace Database\Factories;

use App\Models\PropertyType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyType>
 */
class PropertyTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Imóvel '.$this->faker->unique()->numerify('####'),
            'shows_number' => true,
            'shows_complement' => true,
            'requires_development' => false,
            'shows_unit' => false,
            'shows_block' => false,
        ];
    }

    /**
     * Tipo de imóvel de empreendimento (exige empreendimento, unidade e bloco).
     */
    public function development(): static
    {
        return $this->state(fn (array $attributes): array => [
            'shows_number' => false,
            'shows_complement' => false,
            'requires_development' => true,
            'shows_unit' => true,
            'shows_block' => true,
        ]);
    }
}
