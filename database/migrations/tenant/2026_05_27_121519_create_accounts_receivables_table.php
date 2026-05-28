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
        Schema::create('accounts_receivables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_financial_id')->constrained('category_financials');
            $table->foreignId('financial_subcategory_id')->nullable()->constrained('financial_subcategory');
            $table->foreignId('cost_id')->nullable()->constrained('costs');
            $table->foreignId('account_bank_id')->constrained('account_banks');
            $table->foreignId('contact_financial_id')->constrained('contact_financials');
            $table->text('description');
            $table->integer('total')->nullable();
            $table->integer('payment_method');
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
        Schema::dropIfExists('accounts_receivables');
    }
};
