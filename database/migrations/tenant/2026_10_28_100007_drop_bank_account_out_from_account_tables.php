<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remove `bank_account_out` de contas a pagar e a receber: era um inteiro sem
 * FK e sem uso, já que o lançamento usa `bank_account_id` (Q-S-16).
 */
return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $tables = ['account_payables', 'account_receivables'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasColumn($tableName, 'bank_account_out')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('bank_account_out');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * A coluna volta com o valor 0 nas linhas existentes: o conteúdo original
     * não é recuperável.
     */
    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasColumn($tableName, 'bank_account_out')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->integer('bank_account_out')->default(0);
            });
        }
    }
};
