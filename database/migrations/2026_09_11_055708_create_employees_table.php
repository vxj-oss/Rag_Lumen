<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empleados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->string('correo', 150)->unique();
            $table->string('telefono', 30)->nullable();
            $table->string('cargo', 100)->nullable();
            $table->enum('especialidad', [
                'backend',
                'frontend',
                'fullstack',
                'ux_ui',
                'graphic_design',
                'marketing',
                'seo',
                'advertising',
                'project_manager',
                'other',
            ]);
            $table->enum('estado', ['active', 'inactive', 'on_leave'])->default('active');
            $table->date('fecha_contratacion')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('especialidad');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};
