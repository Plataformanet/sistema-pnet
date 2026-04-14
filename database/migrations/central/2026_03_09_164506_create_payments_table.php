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
        Schema::create('payments', function (Blueprint $table) {
            $table->id()->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained('subscriptions');
            $table->string('mp_payment_id')->nullable()->unique();
            $table->string('mp_preference_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('BRL');
            $table->integer('status')->default(0); // 0 = pendente, 1 = pago, 2 = falhado, 3 = reembolsado
            $table->string('payment_method')->nullable();
            $table->text('description');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->string('invoice_url')->nullable();
            $table->json('metadata')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index('mp_payment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
