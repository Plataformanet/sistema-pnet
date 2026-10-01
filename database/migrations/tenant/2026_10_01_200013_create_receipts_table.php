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
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('proposals')->cascadeOnDelete();
            $table->foreignId('proposal_cost_item_id')->nullable()->constrained('proposal_cost_items')->nullOnDelete();
            $table->string('type', 20);
            $table->string('name', 191);
            $table->string('document', 14);
            $table->string('registration_number', 50)->nullable();
            $table->string('notary_name', 191)->nullable();
            $table->unsignedBigInteger('total_spent')->nullable();
            $table->unsignedBigInteger('amount_deposited')->nullable();
            $table->date('date');
            $table->timestamps();

            $table->index(['proposal_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
