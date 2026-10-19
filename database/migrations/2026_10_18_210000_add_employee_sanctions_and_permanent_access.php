<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_sanctions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados');
            $table->foreignId('aplicado_por')->constrained('users');
            $table->date('fecha_hechos');
            $table->text('hechos');
            $table->string('clausula_infringida')->nullable();
            $table->decimal('monto', 12, 2)->nullable();
            $table->timestamps();
        });
        DB::table('users')->where('force_password_change', true)->update(['force_password_change' => false]);
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_sanctions');
    }
};
