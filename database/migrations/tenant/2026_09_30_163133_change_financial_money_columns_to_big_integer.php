<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Valores monetários em centavos numa coluna `integer` (32 bits) estouram em
 * R$ 21.474.836,47. Passa saldos, totais e parcelas para `bigInteger`,
 * repetindo os atributos originais de cada coluna.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->bigInteger('initial_balance')->nullable()->change();
            $table->bigInteger('current_balance')->nullable()->change();
        });

        Schema::table('account_payables', function (Blueprint $table) {
            $table->bigInteger('total')->nullable()->change();
        });

        Schema::table('account_receivables', function (Blueprint $table) {
            $table->bigInteger('total')->nullable()->change();
        });

        Schema::table('installments', function (Blueprint $table) {
            $table->bigInteger('value')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->integer('initial_balance')->nullable()->change();
            $table->integer('current_balance')->nullable()->change();
        });

        Schema::table('account_payables', function (Blueprint $table) {
            $table->integer('total')->nullable()->change();
        });

        Schema::table('account_receivables', function (Blueprint $table) {
            $table->integer('total')->nullable()->change();
        });

        Schema::table('installments', function (Blueprint $table) {
            $table->integer('value')->change();
        });
    }
};
