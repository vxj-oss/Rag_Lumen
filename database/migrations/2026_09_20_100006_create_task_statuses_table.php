<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estados_tarea', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->nullable()->constrained('proyectos')->cascadeOnDelete();
            $table->string('nombre', 60);
            $table->string('slug', 60);
            $table->string('color', 20)->default('gray');
            $table->unsignedInteger('posicion')->default(0);
            $table->boolean('es_inicial')->default(false);
            $table->boolean('es_final')->default(false);
            $table->boolean('es_bloqueante')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['proyecto_id', 'slug']);
            $table->index('proyecto_id');
        });

        Schema::table('tareas', function (Blueprint $table) {
            $table->foreignId('estado_id')->nullable()->after('estado')->constrained('estados_tarea')->nullOnDelete();
            $table->string('codigo', 60)->nullable()->unique()->after('id');
        });

        Schema::table('proyectos', function (Blueprint $table) {
            $table->unsignedInteger('contador_tareas')->default(0)->after('presupuesto');
        });
    }

    public function down(): void
    {
        Schema::table('proyectos', function (Blueprint $table) {
            $table->dropColumn('contador_tareas');
        });

        Schema::table('tareas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('estado_id');
            $table->dropUnique(['codigo']);
            $table->dropColumn('codigo');
        });

        Schema::dropIfExists('estados_tarea');
    }
};
