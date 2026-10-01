<?php

namespace Database\Seeders;

use App\Enums\ReceiptType;
use App\Models\CostType;
use Illuminate\Database\Seeder;

class CostTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $costTypes = [
            ['name' => 'Registro', 'receipt_type' => null],
            ['name' => 'ITBI', 'receipt_type' => null],
            ['name' => 'Assessoria', 'receipt_type' => ReceiptType::ADVISORY],
            ['name' => 'Matrícula', 'receipt_type' => null],
            ['name' => 'Motoboy', 'receipt_type' => ReceiptType::COURIER],
            ['name' => 'Outros', 'receipt_type' => null],
            ['name' => 'Cartório de Notas', 'receipt_type' => null],
        ];

        foreach ($costTypes as $costType) {
            CostType::withTrashed()->firstOrCreate(
                ['name' => $costType['name']],
                ['requires_notary' => false, 'receipt_type' => $costType['receipt_type']],
            );
        }
    }
}
