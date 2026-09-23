<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proyectos', function (Blueprint $table) {
            $table->foreignId('cliente_id')->nullable()->after('codigo')->constrained('clientes')->nullOnDelete();
            $table->foreignId('empleado_gerente_id')->nullable()->after('empleado_responsable_id')->constrained('empleados')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('proyectos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cliente_id');
            $table->dropConstrainedForeignId('empleado_gerente_id');
        });
    }
};
