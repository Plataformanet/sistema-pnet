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
        Schema::create('financial_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('type');
            $table->text('observations')->nullable();
            $table->boolean('active')->default(1);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['name', 'type'], 'financial_categories_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_categories');
    }
};
