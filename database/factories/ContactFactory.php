<?php

namespace Database\Factories;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
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
            'type' => 'PF',
            'name_corporatereason' => $this->faker->name(),
            'fantasy_name' => null,
            'cpf_cnpj' => $this->faker->cpf(),
            'email' => $this->faker->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'cell_phone' => $this->faker->cellphoneNumber(),
        ];
    }

    /**
     * Contato pessoa jurídica (PJ) com razão social, nome fantasia e CNPJ.
     */
    public function pessoaJuridica(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'PJ',
            'name_corporatereason' => $this->faker->company(),
            'fantasy_name' => $this->faker->companySuffix(),
            'cpf_cnpj' => $this->faker->cnpj(),
        ]);
    }
}
