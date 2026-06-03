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
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('bank')->index();
            $table->string('agency')->index();
            $table->string('account_number');
            $table->string('account_type', 50);
            $table->integer('initial_balance')->nullable();
            $table->integer('current_balance')->nullable();
            $table->boolean('active')->default(1)->index();
            $table->boolean('main_account')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['bank', 'agency', 'account_number'], 'bank_account_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
