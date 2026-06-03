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
        Schema::create('financial_subcategories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_category_id')->constrained('financial_categories')->cascadeOnDelete();
            $table->string('name');
            $table->text('observations')->nullable();
            $table->boolean('active')->default(1);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['name', 'financial_category_id'], 'financial_subcategories_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_subcategories');
    }
};
