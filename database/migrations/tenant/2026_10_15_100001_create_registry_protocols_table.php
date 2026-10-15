<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Protocolos de registro do imóvel no cartório. A proposta é opcional e, se
 * for apagada fisicamente, o protocolo fica sem vínculo em vez de sumir.
 *
 * O número do protocolo é único entre os protocolos ativos (validado no Form
 * Request). O índice do banco não é `unique` porque, sem restauração pela
 * tela, um protocolo excluído por engano precisa poder ser recadastrado com o
 * mesmo número.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('registry_protocols', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->nullable()->constrained('proposals')->nullOnDelete();
            $table->string('protocol_number', 50);
            $table->string('buyer_name', 255);
            $table->string('seller_name', 255)->nullable();
            $table->date('entered_at');
            $table->date('exited_at')->nullable();
            $table->unsignedTinyInteger('position');
            $table->softDeletes();
            $table->timestamps();

            $table->index('protocol_number');
            $table->index('position');
            $table->index('entered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registry_protocols');
    }
};
