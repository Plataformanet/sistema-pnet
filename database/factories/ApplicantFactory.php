<?php

namespace Database\Factories;

use App\Enums\MaritalStatus;
use App\Models\Applicant;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Applicant>
 */
class ApplicantFactory extends Factory
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
            'marital_status' => MaritalStatus::SINGLE,
            'profession' => $this->faker->jobTitle(),
            'declared_income' => 500_000,
        ];
    }
}
