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
        Schema::create('itbi_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('itbi_municipality_id')->unique()->constrained('itbi_municipalities')->cascadeOnDelete();
            $table->decimal('own_funds_rate', 7, 4);
            $table->decimal('financed_rate', 7, 4)->nullable();
            $table->unsignedBigInteger('financed_cap_amount')->nullable();
            $table->decimal('first_property_financed_rate', 7, 4)->nullable();
            $table->decimal('other_property_financed_rate', 7, 4)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('itbi_rates');
    }
};
