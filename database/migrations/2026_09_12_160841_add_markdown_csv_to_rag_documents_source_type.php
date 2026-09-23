<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE documentos_rag MODIFY tipo_origen ENUM('pdf', 'docx', 'txt', 'markdown', 'csv', 'manual', 'other') NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE documentos_rag MODIFY tipo_origen ENUM('pdf', 'docx', 'txt', 'manual', 'other') NOT NULL");
    }
};
