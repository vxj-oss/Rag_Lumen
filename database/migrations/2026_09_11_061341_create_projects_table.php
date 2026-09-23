<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proyectos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->enum('tipo', [
                'software_development',
                'web_development',
                'ux_ui_design',
                'graphic_design',
                'digital_marketing',
                'seo',
                'advertising',
                'branding',
                'campaign',
                'other',
            ]);
            $table->string('client', 150)->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_fin_estimada');
            $table->date('fecha_fin_real')->nullable();
            $table->enum('estado', [
                'planning',
                'in_progress',
                'review',
                'blocked',
                'paused',
                'completed',
                'cancelled',
            ])->default('planning');
            $table->enum('prioridad', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->foreignId('empleado_responsable_id')->nullable()->constrained('empleados')->nullOnDelete();
            $table->decimal('presupuesto', 12, 2)->nullable();
            $table->unsignedTinyInteger('puntuacion_riesgo')->nullable();
            $table->enum('nivel_riesgo', ['low', 'medium', 'high', 'critical'])->nullable();
            $table->dateTime('riesgo_calculado_en')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('estado');
            $table->index('prioridad');
            $table->index('nivel_riesgo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proyectos');
    }
};
