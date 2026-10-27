<?php

namespace Database\Factories;

use App\Enums\ItbiModuleEnum;
use App\Enums\SupportedStateEnum;
use App\Models\ItbiMunicipality;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItbiMunicipality>
 */
class ItbiMunicipalityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->city(),
            'state' => SupportedStateEnum::SP,
            'ibge_code' => $this->faker->unique()->numberBetween(1_000_000, 9_999_999),
            'module' => ItbiModuleEnum::STANDARD,
            'full_rate' => null,
        ];
    }
}
