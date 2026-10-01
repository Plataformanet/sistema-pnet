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
        Schema::create('itbi_brackets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('itbi_municipality_id')->constrained('itbi_municipalities')->cascadeOnDelete();
            $table->unsignedBigInteger('min_value');
            $table->unsignedBigInteger('max_value');
            $table->decimal('rate', 7, 4);
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->timestamps();

            $table->index(['itbi_municipality_id', 'min_value']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('itbi_brackets');
    }
};
