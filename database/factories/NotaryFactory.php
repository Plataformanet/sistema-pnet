<?php

namespace Database\Factories;

use App\Enums\BrazilianState;
use App\Models\Notary;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notary>
 */
class NotaryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Cartório '.$this->faker->unique()->numerify('##º Ofício'),
            'zip_code' => $this->faker->numerify('########'),
            'street' => $this->faker->streetName(),
            'number' => $this->faker->buildingNumber(),
            'complement' => null,
            'neighborhood' => 'Centro',
            'city' => $this->faker->city(),
            'state' => BrazilianState::SP,
            'reference_point' => null,
            'business_hours' => null,
        ];
    }
}
