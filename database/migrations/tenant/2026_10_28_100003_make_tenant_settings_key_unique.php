<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `tenant_settings.key` passa a ser única (incluindo as excluídas por soft
 * delete). Antes de criar o índice, deduplica: de cada chave repetida fica a
 * linha mais recente (maior `id`) e as outras são removidas.
 * Idempotente: se o índice já existe, não faz nada.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasIndex('tenant_settings', 'tenant_settings_key_unique', 'unique')) {
            return;
        }

        DB::transaction(function () {
            $duplicatedKeys = DB::table('tenant_settings')
                ->select('key')
                ->groupBy('key')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('key');

            foreach ($duplicatedKeys as $key) {
                $keepId = DB::table('tenant_settings')->where('key', $key)->max('id');

                DB::table('tenant_settings')->where('key', $key)->where('id', '!=', $keepId)->delete();
            }
        });

        Schema::table('tenant_settings', function (Blueprint $table) {
            if (Schema::hasIndex('tenant_settings', ['key'])) {
                $table->dropIndex(['key']);
            }

            $table->unique('key');
        });
    }

    /**
     * Reverse the migrations.
     *
     * As linhas duplicadas removidas não voltam.
     */
    public function down(): void
    {
        Schema::table('tenant_settings', function (Blueprint $table) {
            $table->dropUnique(['key']);
            $table->index('key');
        });
    }
};
