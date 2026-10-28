<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RN-DOC-PRO-15: registra o envio do alerta de etapa atrasada para que o
 * comando diário não reenvie o mesmo aviso todo dia útil.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('proposal_stages', 'overdue_alerted_at')) {
            return;
        }

        Schema::table('proposal_stages', function (Blueprint $table) {
            $table->timestamp('overdue_alerted_at')->nullable()->after('completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('proposal_stages', 'overdue_alerted_at')) {
            return;
        }

        Schema::table('proposal_stages', function (Blueprint $table) {
            $table->dropColumn('overdue_alerted_at');
        });
    }
};
