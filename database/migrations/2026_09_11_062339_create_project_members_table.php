<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('miembros_proyecto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->cascadeOnDelete();
            $table->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();
            $table->string('rol_en_proyecto', 50);
            $table->date('asignado_en');
            $table->date('retirado_en')->nullable();
            $table->enum('estado', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->unique(['proyecto_id', 'empleado_id']);
            $table->index('empleado_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('miembros_proyecto');
    }
};
