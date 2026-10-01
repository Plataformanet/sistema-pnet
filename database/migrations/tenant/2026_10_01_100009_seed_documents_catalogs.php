<?php

use Database\Seeders\BankSeeder;
use Database\Seeders\ContractTypeSeeder;
use Database\Seeders\CostTypeSeeder;
use Database\Seeders\StageSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Carga inicial dos catálogos do módulo de Documentações. Roda como migration
 * para que tenants já existentes e os novos recebam os mesmos registros; os
 * seeders são idempotentes.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        (new BankSeeder)->run();
        (new ContractTypeSeeder)->run();
        (new CostTypeSeeder)->run();
        (new StageSeeder)->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
