<?php

namespace Database\Factories;

use App\Enums\PropertyConditionEnum;
use App\Enums\ProposalStatusEnum;
use App\Models\Bank;
use App\Models\ContractType;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Proposal>
 */
class ProposalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $creator = User::factory();

        return [
            'creator_id' => $creator,
            'analyst_id' => fn (array $attributes) => $attributes['creator_id'],
            'bank_id' => Bank::factory(),
            'contract_type_id' => ContractType::factory(),
            'status' => ProposalStatusEnum::NEW,
            'property_condition' => PropertyConditionEnum::USED,
            'purchase_value' => 40_000_000,
            'down_payment_value' => 8_000_000,
        ];
    }

    public function withStatus(ProposalStatusEnum $status): static
    {
        return $this->state(fn (array $attributes): array => ['status' => $status]);
    }
}
