<?php

namespace Database\Factories;

use App\Models\Stage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stage>
 */
class StageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order' => $this->faker->unique()->numberBetween(100, 9999),
            'name' => 'Etapa '.$this->faker->unique()->numerify('####'),
            'has_date' => false,
            'date_required' => false,
            'has_upload' => false,
            'upload_required' => false,
            'title_required' => false,
            'notes_required' => false,
            'completion_deadline_hours' => 0,
            'alert_deadline_hours' => 0,
            'shows_property_data' => false,
            'shows_registry_protocol' => false,
        ];
    }
}
