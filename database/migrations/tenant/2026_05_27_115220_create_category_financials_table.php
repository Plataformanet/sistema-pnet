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
        Schema::create('category_financials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('type');
            $table->text('observations')->nullable();
            $table->boolean('active')->default(1);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['name', 'type'], 'category_financials_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_financials');
    }
};
