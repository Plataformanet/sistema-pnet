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
        Schema::create('holder_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->morphs('accountable');
            $table->string('bank_name', 100);
            $table->unsignedTinyInteger('account_type');
            $table->string('branch', 20);
            $table->string('number', 30);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['accountable_type', 'accountable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holder_bank_accounts');
    }
};
