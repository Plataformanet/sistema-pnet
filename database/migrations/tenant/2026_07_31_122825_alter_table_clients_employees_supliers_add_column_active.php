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
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('active')->default(true)->after('contact_id');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->boolean('active')->default(true)->after('hire_date');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->boolean('active')->default(true)->after('supply_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('active');
        });
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('active');
        });
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('active');
        });
    }
};
