<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * O antigo "centro de custo" do financeiro (`costs.type`) passa a ser o catálogo
 * de tipos de custo compartilhado com Propostas. As FKs `cost_id` de contas a
 * pagar/receber continuam válidas: o banco atualiza a referência ao renomear.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('costs', 'cost_types');

        Schema::table('cost_types', function (Blueprint $table) {
            $table->renameColumn('type', 'name');
        });

        Schema::table('cost_types', function (Blueprint $table) {
            $table->boolean('requires_notary')->default(false)->after('name');
            $table->string('receipt_type', 20)->nullable()->after('requires_notary');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cost_types', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropColumn(['requires_notary', 'receipt_type']);
        });

        Schema::table('cost_types', function (Blueprint $table) {
            $table->renameColumn('name', 'type');
        });

        Schema::rename('cost_types', 'costs');
    }
};
