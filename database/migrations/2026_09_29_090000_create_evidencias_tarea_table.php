<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidencias_tarea', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tarea_id')->constrained('tareas')->cascadeOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('tipo', ['archivo', 'enlace']);
            $table->string('ruta_archivo')->nullable();
            $table->string('nombre_archivo')->nullable();
            $table->string('url', 500)->nullable();
            $table->text('comentario')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('tarea_id');
        });

        Schema::table('tareas', function (Blueprint $table) {
            $table->text('motivo_rechazo')->nullable()->after('motivo_bloqueo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidencias_tarea');

        Schema::table('tareas', function (Blueprint $table) {
            $table->dropColumn('motivo_rechazo');
        });
    }
};
