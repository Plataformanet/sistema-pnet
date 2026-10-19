<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Etapas com `bank_id` nulo formam a timeline padrão do sistema; as demais,
     * a timeline personalizada do banco. As etapas existentes ficam na padrão.
     */
    public function up(): void
    {
        Schema::table('stages', function (Blueprint $table) {
            $table->foreignId('bank_id')->nullable()->after('id')->constrained('banks')->restrictOnDelete();
            $table->dropIndex(['order']);
            $table->index(['bank_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stages', function (Blueprint $table) {
            $table->dropForeign(['bank_id']);
            $table->dropIndex(['bank_id', 'order']);
            $table->dropColumn('bank_id');
            $table->index('order');
        });
    }
};
