<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rag_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 200);
            $table->string('file_name', 255);
            $table->string('file_path', 500);
            $table->string('mime_type', 100);
            $table->enum('source_type', ['pdf', 'docx', 'txt', 'manual', 'other']);
            $table->enum('status', ['pending', 'processing', 'processed', 'failed'])->default('pending');
            $table->text('failure_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('project_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rag_documents');
    }
};
