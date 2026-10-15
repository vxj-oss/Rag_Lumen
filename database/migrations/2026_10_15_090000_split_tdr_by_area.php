<?php

use App\Support\TdrPlantillas;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('terminos_referencia', function (Blueprint $table) {
            $table->string('area', 30)->nullable()->unique()->after('id');
            $table->string('nombre', 200)->nullable()->after('area');
            $table->json('entregables')->nullable()->after('contenido');
        });

        Schema::table('faltas', function (Blueprint $table) {
            // null = falta general (aplica a todas las áreas)
            $table->string('area', 30)->nullable()->after('gravedad');
        });

        Schema::table('empleados', function (Blueprint $table) {
            $table->foreignId('normativa_tdr_id')->nullable()->after('normativa_version_aceptada')
                ->constrained('terminos_referencia')->nullOnDelete();
        });

        // El TDR único anterior se reemplaza por uno por área; las aceptaciones previas dejan de valer.
        DB::table('empleados')->update(['normativa_version_aceptada' => null, 'normativa_aceptada_en' => null]);
        DB::table('terminos_referencia')->whereNull('area')->delete();

        foreach (TdrPlantillas::todas() as $area => $plantilla) {
            DB::table('terminos_referencia')->insert([
                'area' => $area,
                'nombre' => $plantilla['nombre'],
                'contenido' => $plantilla['contenido'],
                'entregables' => json_encode($plantilla['entregables'], JSON_UNESCAPED_UNICODE),
                'version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropConstrainedForeignId('normativa_tdr_id');
        });

        Schema::table('faltas', function (Blueprint $table) {
            $table->dropColumn('area');
        });

        Schema::table('terminos_referencia', function (Blueprint $table) {
            $table->dropUnique(['area']);
            $table->dropColumn(['area', 'nombre', 'entregables']);
        });
    }
};
