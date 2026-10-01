<?php

namespace Database\Factories;

use App\Models\CostType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CostType>
 */
class CostTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Custo '.$this->faker->unique()->numerify('####'),
            'requires_notary' => false,
            'receipt_type' => null,
        ];
    }
}
