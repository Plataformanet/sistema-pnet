<?php

namespace Database\Factories;

use App\Models\Address;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'zip_code'     => '12345678',
            'street'       => $this->faker->streetName,
            'number'       => $this->faker->numberBetween(1, 9999),
            'complement'   => $this->faker->secondaryAddress,
            'neighborhood' => $this->faker->word,
            'city'         => $this->faker->city,
            'state'        => $this->faker->stateAbbr,
        ];
    }
}
