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
        Schema::create('drives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('drive_folder_id')->constrained('drive_folders')->cascadeOnDelete();
            $table->string('name');
            $table->string('document_path');
            $table->string('document_size');
            $table->string('document_type', 45);
            $table->foreignId('modified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('modified_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drives');
    }
};
