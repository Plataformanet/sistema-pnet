<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `permissions.name` do catálogo central passa a ser único (RN-CORE-05).
 * Antes do índice, deduplica: de cada nome repetido fica a linha de menor `id`
 * (a primeira do catálogo, a que os módulos já referenciam).
 * Idempotente: se o índice já existe, não faz nada.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasIndex('permissions', 'permissions_name_unique', 'unique')) {
            return;
        }

        DB::transaction(function () {
            $duplicatedNames = DB::table('permissions')
                ->select('name')
                ->groupBy('name')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('name');

            foreach ($duplicatedNames as $name) {
                $keepId = DB::table('permissions')->where('name', $name)->min('id');

                DB::table('permissions')->where('name', $name)->where('id', '!=', $keepId)->delete();
            }
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->unique('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropUnique(['name']);
        });
    }
};
