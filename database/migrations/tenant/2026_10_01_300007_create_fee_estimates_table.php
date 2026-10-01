<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fee_estimates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->nullable()->unique()->constrained('quotes')->cascadeOnDelete();
            $table->foreignId('proposal_id')->nullable()->unique()->constrained('proposals')->nullOnDelete();
            $table->unsignedTinyInteger('type');
            $table->char('state', 2);
            $table->string('municipality_name', 120);
            $table->date('valid_until')->nullable();
            $table->json('api_result');
            $table->unsignedBigInteger('fees_total');
            $table->unsignedBigInteger('itbi_amount')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_estimates');
    }
};
