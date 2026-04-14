<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Plano Básico',
                'slug' => 'basic',
                'price' => 29.99,
            ],
            [
                'name' => 'Plano Padrão',
                'slug' => 'standard',
                'price' => 59.99,
            ],
            [
                'name' => 'Plano Premium',
                'slug' => 'premium',
                'price' => 99.99,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::create($plan);
        }
    }
}
