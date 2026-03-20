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
        Schema::create('plan_modules', function (Blueprint $table) {
            $table->id('id')->primary();
            $table->unsignedBigInteger('plan_id')->constrained('plans')->onDelete('cascade');
            $table->unsignedBigInteger('module_id')->constrained('modules')->onDelete('cascade');
            $table->boolean('is_included')->default(true);
            $table->decimal('additional_price', 10, 2)->default(0.00);
            $table->timestamps();

            $table->unique(['plan_id', 'module_id']); // Evita duplicatas para o mesmo plano e módulo
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_modules');
    }
};
