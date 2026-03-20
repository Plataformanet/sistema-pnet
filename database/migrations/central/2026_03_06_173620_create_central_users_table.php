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
        Schema::create('central_users', function (Blueprint $table) {
            $table->id('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->integer('role')->default(1); // 0 = admin, 1 = manager
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('central_users');
    }
};
