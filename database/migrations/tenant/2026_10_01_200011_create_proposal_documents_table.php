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
        Schema::create('proposal_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('proposals')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('owner', 20);
            $table->nullableMorphs('documentable');
            $table->foreignId('proposal_stage_id')->nullable()->constrained('proposal_stages')->nullOnDelete();
            $table->string('type', 40)->nullable();
            $table->string('title', 191);
            $table->string('disk', 30);
            $table->string('path', 255);
            $table->string('original_name', 255);
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();

            $table->index(['proposal_id', 'owner']);
        });

        Schema::table('proposal_stages', function (Blueprint $table) {
            $table->foreign('proposal_document_id')->references('id')->on('proposal_documents')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proposal_stages', function (Blueprint $table) {
            $table->dropForeign(['proposal_document_id']);
        });

        Schema::dropIfExists('proposal_documents');
    }
};
