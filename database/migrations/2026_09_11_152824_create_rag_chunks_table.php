<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rag_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('rag_documents')->cascadeOnDelete();
            $table->text('content');
            $table->unsignedInteger('chunk_index');
            $table->json('metadata')->nullable();
            $table->json('vector_reference')->nullable();
            $table->timestamps();

            $table->unique(['document_id', 'chunk_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rag_chunks');
    }
};
