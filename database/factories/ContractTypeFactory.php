<?php

namespace Database\Factories;

use App\Models\ContractType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContractType>
 */
class ContractTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Contrato '.$this->faker->unique()->numerify('####'),
            'requires_financing' => true,
        ];
    }

    /**
     * Contrato sem financiamento (equivalente ao "AQUISIÇÃO À VISTA COM FGTS" do legado).
     */
    public function withoutFinancing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'requires_financing' => false,
        ]);
    }
}
