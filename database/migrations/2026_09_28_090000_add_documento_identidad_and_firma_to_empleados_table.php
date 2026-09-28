<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->string('documento_identidad', 20)->nullable()->unique()->after('apellidos');
            $table->string('firma_path')->nullable()->after('fecha_contratacion');
        });
    }

    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropUnique(['documento_identidad']);
            $table->dropColumn(['documento_identidad', 'firma_path']);
        });
    }
};
