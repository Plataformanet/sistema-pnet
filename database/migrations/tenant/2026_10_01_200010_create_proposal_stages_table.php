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
        Schema::create('proposal_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('proposals')->cascadeOnDelete();
            $table->foreignId('stage_id')->constrained('stages')->restrictOnDelete();
            $table->unsignedSmallInteger('position');
            $table->unsignedBigInteger('proposal_document_id')->nullable();
            $table->date('date')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['proposal_id', 'is_current']);
            $table->index(['proposal_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposal_stages');
    }
};
