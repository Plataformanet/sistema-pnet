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
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('analyst_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('bank_id')->constrained('banks')->restrictOnDelete();
            $table->foreignId('contract_type_id')->constrained('contract_types')->restrictOnDelete();
            $table->string('amortization_table', 10)->nullable();
            $table->string('status', 30)->index();
            $table->string('property_condition', 10);
            $table->boolean('has_other_property')->default(false);
            $table->unsignedBigInteger('purchase_value');
            $table->unsignedBigInteger('down_payment_value');
            $table->unsignedBigInteger('financing_value')->nullable();
            $table->unsignedBigInteger('expenses_value')->nullable();
            $table->unsignedBigInteger('subsidy_value')->nullable();
            $table->unsignedBigInteger('financed_value')->nullable();
            $table->unsignedBigInteger('intended_installment_value')->nullable();
            $table->unsignedBigInteger('fgts_value')->nullable();
            $table->boolean('uses_fgts')->default(false);
            $table->boolean('is_first_financing')->default(false);
            $table->boolean('finance_documentation_fee')->default(false);
            $table->unsignedBigInteger('documentation_fee_to_finance')->nullable();
            $table->unsignedSmallInteger('payment_term')->nullable();
            $table->boolean('declares_income_tax')->default(false);
            $table->unsignedBigInteger('declared_income')->nullable();
            $table->unsignedTinyInteger('expected_delivery_month')->nullable();
            $table->unsignedSmallInteger('expected_delivery_year')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->text('restriction_reason')->nullable();
            $table->text('contract_notes')->nullable();
            $table->text('purchase_value_notes')->nullable();
            $table->text('down_payment_notes')->nullable();
            $table->text('fgts_notes')->nullable();
            $table->text('documentation_fee_notes')->nullable();
            $table->text('documentation_financing_notes')->nullable();
            $table->text('income_tax_notes')->nullable();
            $table->text('general_notes')->nullable();
            $table->text('particularities')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
