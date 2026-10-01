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
        Schema::create('proposal_cost_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('proposals')->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('description', 191)->nullable();
            $table->string('extra_fee_description', 191)->nullable();
            $table->text('service_description')->nullable();
            $table->boolean('generates_receipt')->default(false);
            $table->unsignedBigInteger('amount');
            $table->foreignId('cost_type_id')->nullable()->constrained('cost_types')->restrictOnDelete();
            $table->foreignId('notary_id')->nullable()->constrained('notaries')->restrictOnDelete();
            $table->date('date')->nullable();
            $table->text('notes')->nullable();
            $table->string('bill_path', 255)->nullable();
            $table->string('proof_path', 255)->nullable();
            $table->timestamps();

            $table->index(['proposal_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposal_cost_items');
    }
};
