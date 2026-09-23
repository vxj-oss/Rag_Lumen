<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('areas_proyecto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->cascadeOnDelete();
            $table->foreignId('area_id')->constrained('areas')->cascadeOnDelete();
            $table->foreignId('lider_area_id')->nullable()->constrained('empleados')->nullOnDelete();
            $table->decimal('porcentaje_presupuesto', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['proyecto_id', 'area_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('areas_proyecto');
    }
};
