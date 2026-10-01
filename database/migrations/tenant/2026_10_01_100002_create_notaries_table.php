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
        Schema::create('notaries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191)->index();
            $table->string('zip_code', 8);
            $table->string('street', 191);
            $table->string('number', 20);
            $table->string('complement', 45)->nullable();
            $table->string('neighborhood', 45);
            $table->string('city', 80);
            $table->char('state', 2);
            $table->string('reference_point', 100)->nullable();
            $table->text('business_hours')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notaries');
    }
};
