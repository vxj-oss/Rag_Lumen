<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registros_actividad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sujeto_tipo');
            $table->unsignedBigInteger('sujeto_id');
            $table->string('accion', 40);
            $table->string('descripcion', 500);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['sujeto_tipo', 'sujeto_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registros_actividad');
    }
};
