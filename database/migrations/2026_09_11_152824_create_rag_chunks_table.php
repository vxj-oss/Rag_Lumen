<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fragmentos_rag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('documento_id')->constrained('documentos_rag')->cascadeOnDelete();
            $table->text('contenido');
            $table->unsignedInteger('indice_fragmento');
            $table->json('metadatos')->nullable();
            $table->json('referencia_vector')->nullable();
            $table->timestamps();

            $table->unique(['documento_id', 'indice_fragmento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fragmentos_rag');
    }
};
