<?php

use App\Enums\ReceiptType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Carga inicial dos catálogos do módulo de Documentações. Roda como migration
 * para que tenants já existentes e os novos recebam os mesmos registros.
 * Idempotente: só insere o que ainda não existe, contando também os registros
 * excluídos (soft delete), para não ressuscitar o que o tenant apagou.
 */
return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $banks = ['Banco do Brasil', 'Bradesco', 'Itaú', 'Santander', 'Caixa'];

    /**
     * Os ids do legado são preservados para permitir a migração de dados das
     * propostas. "AQUISIÇÃO À VISTA COM FGTS" (id 4) é o único sem financiamento,
     * substituindo a regra fixa `contrato_id != 4`.
     *
     * @var array<int, array{id: int, name: string, requires_financing: bool}>
     */
    private array $contractTypes = [
        ['id' => 1, 'name' => 'CCFGTS', 'requires_financing' => true],
        ['id' => 2, 'name' => 'PRÓ-COTISTA', 'requires_financing' => true],
        ['id' => 3, 'name' => 'CCSBPE', 'requires_financing' => true],
        ['id' => 4, 'name' => 'AQUISIÇÃO À VISTA COM FGTS', 'requires_financing' => false],
        ['id' => 5, 'name' => 'MCMV', 'requires_financing' => true],
        ['id' => 6, 'name' => 'CCSBPE IPCA', 'requires_financing' => true],
        ['id' => 8, 'name' => 'CCSBPE + Poupança', 'requires_financing' => true],
        ['id' => 9, 'name' => 'Cartório de Notas', 'requires_financing' => true],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        $this->seedBanks($now);
        $this->seedContractTypes($now);
        $this->seedCostTypes($now);
        $this->seedStages($now);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }

    private function seedBanks(DateTimeInterface $now): void
    {
        $existing = DB::table('banks')->whereIn('name', $this->banks)->pluck('name')->all();

        $rows = collect($this->banks)
            ->reject(fn (string $name) => in_array($name, $existing, true))
            ->map(fn (string $name) => ['name' => $name, 'created_at' => $now, 'updated_at' => $now])
            ->values()
            ->all();

        if ($rows !== []) {
            DB::table('banks')->insert($rows);
        }
    }

    private function seedContractTypes(DateTimeInterface $now): void
    {
        $existing = DB::table('contract_types')
            ->whereIn('id', array_column($this->contractTypes, 'id'))
            ->orWhereIn('name', array_column($this->contractTypes, 'name'))
            ->get(['id', 'name']);

        $rows = collect($this->contractTypes)
            ->reject(fn (array $contractType) => $existing->contains('id', $contractType['id'])
                || $existing->contains('name', $contractType['name']))
            ->map(fn (array $contractType) => [...$contractType, 'created_at' => $now, 'updated_at' => $now])
            ->values()
            ->all();

        if ($rows !== []) {
            DB::table('contract_types')->insert($rows);
        }
    }

    private function seedCostTypes(DateTimeInterface $now): void
    {
        $costTypes = [
            ['name' => 'Registro', 'receipt_type' => null],
            ['name' => 'ITBI', 'receipt_type' => null],
            ['name' => 'Assessoria', 'receipt_type' => ReceiptType::ADVISORY->value],
            ['name' => 'Matrícula', 'receipt_type' => null],
            ['name' => 'Motoboy', 'receipt_type' => ReceiptType::COURIER->value],
            ['name' => 'Outros', 'receipt_type' => null],
            ['name' => 'Cartório de Notas', 'receipt_type' => null],
        ];

        $existing = DB::table('cost_types')->whereIn('name', array_column($costTypes, 'name'))->pluck('name')->all();

        $rows = collect($costTypes)
            ->reject(fn (array $costType) => in_array($costType['name'], $existing, true))
            ->map(fn (array $costType) => [
                ...$costType,
                'requires_notary' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->all();

        if ($rows !== []) {
            DB::table('cost_types')->insert($rows);
        }
    }

    /**
     * Etapas ativas da timeline. Os flags de exibição substituem o comportamento
     * fixo do legado (dados do imóvel na etapa 2, protocolo de registro na 7).
     * Prazos e obrigatoriedades são ajustados pelo administrador no cadastro.
     * Só semeia um catálogo vazio: a ordem das etapas é do tenant.
     */
    private function seedStages(DateTimeInterface $now): void
    {
        if (DB::table('stages')->exists()) {
            return;
        }

        $stages = [
            ['order' => 1, 'name' => 'Cadastro/Aprovação'],
            ['order' => 2, 'name' => 'Solicitação de Engenharia', 'shows_property_data' => true],
            ['order' => 3, 'name' => 'Saque de FGTS', 'has_date' => true, 'date_required' => true],
            ['order' => 4, 'name' => 'Entrevista'],
            ['order' => 5, 'name' => 'Conformidade'],
            ['order' => 6, 'name' => 'Assinatura de Escritura'],
            ['order' => 7, 'name' => 'Envio para Registro', 'shows_registry_protocol' => true],
            ['order' => 8, 'name' => 'Finalização'],
        ];

        DB::table('stages')->insert(array_map(fn (array $stage) => [
            'has_date' => false,
            'date_required' => false,
            'shows_property_data' => false,
            'shows_registry_protocol' => false,
            ...$stage,
            'created_at' => $now,
            'updated_at' => $now,
        ], $stages));
    }
};
