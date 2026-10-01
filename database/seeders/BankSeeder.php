<?php

namespace Database\Seeders;

use App\Models\Bank;
use Illuminate\Database\Seeder;

class BankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $banks = ['Banco do Brasil', 'Bradesco', 'Itaú', 'Santander', 'Caixa'];

        foreach ($banks as $name) {
            Bank::withTrashed()->firstOrCreate(['name' => $name]);
        }
    }
}
