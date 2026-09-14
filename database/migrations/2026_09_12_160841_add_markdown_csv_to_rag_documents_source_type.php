<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE rag_documents MODIFY source_type ENUM('pdf', 'docx', 'txt', 'markdown', 'csv', 'manual', 'other') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE rag_documents MODIFY source_type ENUM('pdf', 'docx', 'txt', 'manual', 'other') NOT NULL");
    }
};
