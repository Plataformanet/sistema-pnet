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
        Schema::create('stages', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('order')->index();
            $table->string('name', 191);
            $table->boolean('has_date')->default(false);
            $table->boolean('date_required')->default(false);
            $table->boolean('has_upload')->default(false);
            $table->boolean('upload_required')->default(false);
            $table->boolean('title_required')->default(false);
            $table->boolean('notes_required')->default(false);
            $table->unsignedInteger('completion_deadline_hours')->default(0);
            $table->unsignedInteger('alert_deadline_hours')->default(0);
            $table->boolean('shows_property_data')->default(false);
            $table->boolean('shows_registry_protocol')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stages');
    }
};
