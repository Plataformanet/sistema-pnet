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
        Schema::create('applicants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->unique()->constrained('contacts')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('birth_date')->nullable();
            $table->unsignedTinyInteger('marital_status')->nullable();
            $table->string('profession', 191)->nullable();
            $table->unsignedBigInteger('family_income')->nullable();
            $table->unsignedBigInteger('declared_income')->nullable();
            $table->boolean('declares_income_tax')->default(false);
            $table->text('income_tax_notes')->nullable();
            $table->boolean('by_power_of_attorney')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applicants');
    }
};
