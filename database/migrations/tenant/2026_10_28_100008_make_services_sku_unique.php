<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RN-CAT-01: `services.sku` é único, incluindo os serviços excluídos (soft
 * delete), como o `unique` do banco e a validação já tratam. Se houver SKU
 * repetido, a migration falha listando-os, sem alterar nada: a limpeza é
 * manual (V-02). Idempotente.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasIndex('services', 'services_sku_unique', 'unique')) {
            return;
        }

        $duplicates = DB::table('services')
            ->select('sku', DB::raw('GROUP_CONCAT(id ORDER BY id) as ids'))
            ->groupBy('sku')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            $list = $duplicates->map(fn (object $row): string => "'{$row->sku}' (serviços {$row->ids})")->implode('; ');

            throw new RuntimeException("SKUs repetidos em services.sku impedem o índice unique: {$list}.");
        }

        Schema::table('services', function (Blueprint $table) {
            $table->unique('sku');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropUnique(['sku']);
        });
    }
};
