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
        Schema::create('proponents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained('contacts')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->tinyInteger('income_tax_return')->default(0);
            $table->decimal('reported_income', 10, 2)->nullable();
            $table->text('income_tax_observation')->nullable();
            $table->date('birth_date')->nullable();
            $table->decimal('family_income', 10, 2);
            $table->string('marital_status');
            $table->string('profession');
            $table->tinyInteger('out_of_obligation')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proponents');
    }
};
