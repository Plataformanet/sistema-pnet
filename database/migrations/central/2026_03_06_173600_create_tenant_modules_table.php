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
        Schema::create('tenant_modules', function (Blueprint $table) {
            $table->id('id')->primary();
            $table->unsignedBigInteger('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->unsignedBigInteger('module_id')->constrained('modules')->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->timestamp('activated_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_modules');
    }
};
