<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_product_id')->constrained('category_products')->onDelete('cascade');
            $table->string('name');
            $table->string('sku');
            $table->string('barcode');
            $table->integer('cost_value');
            $table->integer('sell_value');
            $table->boolean('manage_stock')->default(false);
            $table->integer('current_stock')->nullable();
            $table->integer('min_stock')->nullable();
            $table->string('unit_of_measure');
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
