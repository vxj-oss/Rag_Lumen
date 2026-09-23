<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dependencias_tarea', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tarea_id')->constrained('tareas')->cascadeOnDelete();
            $table->foreignId('depende_de_tarea_id')->constrained('tareas')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tarea_id', 'depende_de_tarea_id']);
            $table->index('depende_de_tarea_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dependencias_tarea');
    }
};
