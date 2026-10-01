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
        Schema::create('fee_calculations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('type');
            $table->char('state', 2);
            $table->unsignedInteger('municipality_ibge_code');
            $table->string('municipality_name', 120);
            $table->json('input');
            $table->json('api_result');
            $table->unsignedBigInteger('fees_total');
            $table->unsignedBigInteger('itbi_amount')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_calculations');
    }
};
