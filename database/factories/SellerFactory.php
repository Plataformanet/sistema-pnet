<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\Seller;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Seller>
 */
class SellerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contact_id' => Contact::factory(),
        ];
    }

    /**
     * Vendedor pessoa jurídica (construtora/incorporadora).
     */
    public function company(): static
    {
        return $this->state(fn (array $attributes): array => [
            'contact_id' => Contact::factory()->pessoaJuridica(),
        ]);
    }
}
