<?php

namespace Database\Seeders;

use App\Models\ContractType;
use Illuminate\Database\Seeder;

class ContractTypeSeeder extends Seeder
{
    /**
     * Os ids do legado são preservados para permitir a migração de dados das
     * propostas. "AQUISIÇÃO À VISTA COM FGTS" (id 4) é o único sem financiamento,
     * substituindo a regra fixa `contrato_id != 4`.
     */
    public function run(): void
    {
        $contractTypes = [
            ['id' => 1, 'name' => 'CCFGTS', 'requires_financing' => true],
            ['id' => 2, 'name' => 'PRÓ-COTISTA', 'requires_financing' => true],
            ['id' => 3, 'name' => 'CCSBPE', 'requires_financing' => true],
            ['id' => 4, 'name' => 'AQUISIÇÃO À VISTA COM FGTS', 'requires_financing' => false],
            ['id' => 5, 'name' => 'MCMV', 'requires_financing' => true],
            ['id' => 6, 'name' => 'CCSBPE IPCA', 'requires_financing' => true],
            ['id' => 8, 'name' => 'CCSBPE + Poupança', 'requires_financing' => true],
            ['id' => 9, 'name' => 'Cartório de Notas', 'requires_financing' => true],
        ];

        foreach ($contractTypes as $contractType) {
            if (ContractType::withTrashed()->whereKey($contractType['id'])->orWhere('name', $contractType['name'])->exists()) {
                continue;
            }

            ContractType::forceCreate($contractType);
        }
    }
}
