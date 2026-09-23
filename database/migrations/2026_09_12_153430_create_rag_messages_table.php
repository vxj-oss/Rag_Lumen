<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mensajes_rag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversacion_id')->constrained('conversaciones_rag')->cascadeOnDelete();
            $table->enum('rol', ['user', 'assistant']);
            $table->text('contenido');
            $table->json('fuentes')->nullable();
            $table->timestamps();

            $table->index('conversacion_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mensajes_rag');
    }
};
