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
       Schema::create('payment_methods', function (Blueprint $table) {
            $table->id('id')->primary();
            $table->unsignedBigInteger('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('mp_card_id')->nullable();
            $table->integer('type')->default(0); // 0 = cartão de crédito, 1 = boleto, 2 = pix
            $table->string('brand')->nullable();
            $table->string('last_four');
            $table->string('holder_name');
            $table->integer('expiration_month');
            $table->integer('expiration_year');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::dropIfExists('payment_methods');
    }
};
