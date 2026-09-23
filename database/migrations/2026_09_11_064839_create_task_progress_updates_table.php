<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('avances_tarea', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tarea_id')->constrained('tareas')->cascadeOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('porcentaje_anterior');
            $table->unsignedTinyInteger('porcentaje_nuevo');
            $table->text('comentario')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('tarea_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avances_tarea');
    }
};
