<?php

namespace Database\Factories;

use App\Models\BillableService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BillableService>
 */
class BillableServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Serviço '.$this->faker->unique()->numerify('####'),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->numberBetween(10_000, 500_000),
            'generates_receipt' => false,
        ];
    }
}
