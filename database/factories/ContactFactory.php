<?php

namespace Database\Factories;

use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Model>
 */
class ContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type'                 => 'PF',
            'name_corporatereason' => $this->faker->name,
            'cpf_cnpj'             => '123.456.789-00',
            'email'                => $this->faker->email,
            'phone'                => $this->faker->phoneNumber,
            'cell_phone'           => $this->faker->phoneNumber,
        ];
    }
}
