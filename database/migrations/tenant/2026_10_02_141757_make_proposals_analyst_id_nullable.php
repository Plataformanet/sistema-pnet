<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A proposta pode ficar sem analista até alguém da equipe assumi-la (ex.:
 * proposta cadastrada por um parceiro). Só a nulidade muda; a chave
 * estrangeira para `users` continua com `restrict`.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->unsignedBigInteger('analyst_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations. Propostas sem analista voltam a ter o criador
     * como analista, como era antes.
     */
    public function down(): void
    {
        DB::table('proposals')->whereNull('analyst_id')->update(['analyst_id' => DB::raw('creator_id')]);

        Schema::table('proposals', function (Blueprint $table) {
            $table->unsignedBigInteger('analyst_id')->nullable(false)->change();
        });
    }
};
