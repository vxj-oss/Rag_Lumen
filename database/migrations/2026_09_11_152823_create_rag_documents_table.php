<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos_rag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->nullable()->constrained('proyectos')->nullOnDelete();
            $table->foreignId('subido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('titulo', 200);
            $table->string('nombre_archivo', 255);
            $table->string('ruta_archivo', 500);
            $table->string('tipo_mime', 100);
            $table->enum('tipo_origen', ['pdf', 'docx', 'txt', 'manual', 'other']);
            $table->enum('estado', ['pending', 'processing', 'processed', 'failed'])->default('pending');
            $table->text('motivo_fallo')->nullable();
            $table->json('metadatos')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('proyecto_id');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_rag');
    }
};
