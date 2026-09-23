<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $names = DB::table('proyectos')
            ->whereNull('cliente_id')
            ->whereNotNull('client')
            ->distinct()
            ->pluck('client');

        foreach ($names as $name) {
            $clientId = DB::table('clientes')->where('nombre', $name)->value('id');

            if ($clientId === null) {
                $clientId = DB::table('clientes')->insertGetId([
                    'nombre' => $name,
                    'estado' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('proyectos')->whereNull('cliente_id')->where('client', $name)->update(['cliente_id' => $clientId]);
        }

        Schema::table('proyectos', function (Blueprint $table) {
            $table->dropColumn('client');
        });
    }

    public function down(): void
    {
        Schema::table('proyectos', function (Blueprint $table) {
            $table->string('client', 150)->nullable()->after('tipo');
        });
    }
};
