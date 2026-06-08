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
        Schema::create('account_receivables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_category_id')->constrained('financial_categories');
            $table->foreignId('financial_subcategory_id')->nullable()->constrained('financial_subcategories');
            $table->foreignId('cost_id')->nullable()->constrained('costs');
            $table->foreignId('bank_account_id')->constrained('bank_accounts');
            $table->foreignId('financial_contact_id')->constrained('financial_contacts');
            $table->text('description');
            $table->integer('total')->nullable();
            $table->string('payment_method');
            $table->string('payment_condition', 50);
            $table->integer('total_installments');
            $table->integer('bank_account_out');
            $table->text('observations')->nullable();
            $table->string('receipt')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_receivables');
    }
};
