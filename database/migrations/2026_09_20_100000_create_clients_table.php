<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('identificacion_fiscal', 50)->nullable()->unique();
            $table->string('nombre_contacto', 150)->nullable();
            $table->string('correo', 150)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('sector', 100)->nullable();
            $table->enum('estado', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
