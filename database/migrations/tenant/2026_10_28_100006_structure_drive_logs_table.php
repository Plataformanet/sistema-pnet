<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RN-DRV-05: o log do Drive deixa de ser um JSON solto (`log`) e ganha colunas
 * próprias: `user_id`, `drive_id`, `action`, `ip_address` e `meta`. Os logs
 * existentes (todos de exclusão definitiva) viram `action = permanent_delete`
 * com o JSON antigo integralmente em `meta`.
 *
 * `drive_id` não tem FK de propósito: o log da exclusão definitiva precisa
 * sobreviver ao registro que ele descreve.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('drive_logs', 'log')) {
            return;
        }

        Schema::table('drive_logs', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('drive_id')->nullable()->after('user_id')->index();
            $table->string('action', 30)->nullable()->after('drive_id')->index();
            $table->string('ip_address', 45)->nullable()->after('action');
            $table->json('meta')->nullable()->after('ip_address');
        });

        DB::table('drive_logs')->update([
            'meta' => DB::raw('`log`'),
            'action' => 'permanent_delete',
        ]);

        Schema::table('drive_logs', function (Blueprint $table) {
            $table->dropColumn('log');
            $table->string('action', 30)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * Só os logs de exclusão definitiva têm equivalente no formato antigo; os
     * demais são descartados.
     */
    public function down(): void
    {
        if (Schema::hasColumn('drive_logs', 'log')) {
            return;
        }

        Schema::table('drive_logs', function (Blueprint $table) {
            $table->json('log')->nullable()->after('id');
        });

        DB::table('drive_logs')->where('action', '!=', 'permanent_delete')->delete();
        DB::table('drive_logs')->update(['log' => DB::raw('COALESCE(`meta`, JSON_OBJECT())')]);

        Schema::table('drive_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['drive_id', 'action', 'ip_address', 'meta']);
            $table->json('log')->nullable(false)->change();
        });
    }
};
