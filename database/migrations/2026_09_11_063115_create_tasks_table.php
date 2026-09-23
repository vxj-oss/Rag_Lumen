<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('tareas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->cascadeOnDelete();
            $table->foreignId('asignado_a')->nullable()->constrained('empleados')->nullOnDelete();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('titulo', 200);
            $table->text('descripcion')->nullable();
            $table->enum('estado', ['pending', 'in_progress', 'review', 'blocked', 'completed', 'cancelled'])->default('pending');
            $table->enum('prioridad', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->dateTime('completado_en')->nullable();
            $table->unsignedTinyInteger('porcentaje_progreso')->default(0);
            $table->decimal('horas_estimadas', 6, 2)->nullable();
            $table->decimal('horas_reales', 6, 2)->nullable();
            $table->text('motivo_bloqueo')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('proyecto_id');
            $table->index('asignado_a');
            $table->index('estado');
            $table->index('prioridad');
            $table->index('fecha_vencimiento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tareas');
    }
};
