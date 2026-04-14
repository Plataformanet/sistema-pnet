<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Plan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlanModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = Module::where('is_core', true)->get();

        foreach ($modules as $module) {
            $module->plans()->attach(Plan::where('slug', 'basic')->first()->id, [
                'is_included' => true,
                'additional_price' => 0.00,
            ]);
        }
    }
}
