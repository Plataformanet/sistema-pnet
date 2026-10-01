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
        Schema::create('itbi_municipalities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->char('state', 2);
            $table->unsignedInteger('ibge_code')->unique();
            $table->string('module', 30);
            $table->decimal('full_rate', 7, 4)->nullable();
            $table->timestamps();

            $table->index(['state', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('itbi_municipalities');
    }
};
