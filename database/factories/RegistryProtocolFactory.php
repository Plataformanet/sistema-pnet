<?php

namespace Database\Factories;

use App\Enums\RegistryProtocolPositionEnum;
use App\Models\Proposal;
use App\Models\RegistryProtocol;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegistryProtocol>
 */
class RegistryProtocolFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'proposal_id' => null,
            'protocol_number' => $this->faker->unique()->numerify('######'),
            'buyer_name' => $this->faker->name(),
            'seller_name' => $this->faker->name(),
            'entered_at' => $this->faker->dateTimeBetween('-3 months')->format('Y-m-d'),
            'exited_at' => null,
            'position' => RegistryProtocolPositionEnum::IN_REGISTRY_PG,
        ];
    }

    public function withPosition(RegistryProtocolPositionEnum $position): static
    {
        return $this->state(fn (array $attributes): array => ['position' => $position]);
    }

    public function underAnalysis(): static
    {
        return $this->withPosition(RegistryProtocolPositionEnum::UNDER_ANALYSIS);
    }

    public function requirement(): static
    {
        return $this->withPosition(RegistryProtocolPositionEnum::REQUIREMENT);
    }

    /**
     * Protocolo encerrado: saiu do cartório e aguarda o pagamento do banco.
     */
    public function awaitingBankPayment(): static
    {
        return $this->state(fn (array $attributes): array => [
            'position' => RegistryProtocolPositionEnum::AWAITING_BANK_PAYMENT,
            'exited_at' => $attributes['entered_at'],
        ]);
    }

    /**
     * Protocolo encerrado: registro concluído.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'position' => RegistryProtocolPositionEnum::COMPLETED,
            'exited_at' => $attributes['entered_at'],
        ]);
    }

    public function withProposal(?Proposal $proposal = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'proposal_id' => $proposal?->id ?? Proposal::factory(),
        ]);
    }
}
