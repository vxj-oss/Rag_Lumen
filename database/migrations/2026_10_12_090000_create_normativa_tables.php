<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faltas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->string('titulo', 200);
            $table->text('descripcion');
            $table->enum('gravedad', ['leve', 'grave', 'muy_grave'])->default('leve');
            $table->text('sancion')->nullable();
            $table->decimal('monto', 10, 2)->nullable();
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });

        // Una sola fila: el TDR vigente. `version` sube con cada cambio del TDR o del manual de faltas.
        Schema::create('terminos_referencia', function (Blueprint $table) {
            $table->id();
            $table->longText('contenido');
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
        });

        Schema::table('empleados', function (Blueprint $table) {
            $table->unsignedInteger('normativa_version_aceptada')->nullable();
            $table->timestamp('normativa_aceptada_en')->nullable();
        });

        DB::table('terminos_referencia')->insert([
            'contenido' => implode("\n\n", [
                "1. OBJETO\nEstablecer los términos de referencia (TDR) bajo los cuales EL COLABORADOR ejecuta las actividades que LA EMPRESA le asigne dentro de los proyectos.",
                "2. ALCANCE DEL TRABAJO\nCada proyecto se divide en actividades; cada actividad se compone de tareas. EL COLABORADOR ejecuta las tareas asignadas y las da por completadas únicamente al presentar su evidencia (archivos, imágenes o enlaces).",
                "3. ENTREGABLES Y EVIDENCIA\nToda tarea debe respaldarse con evidencia verificable. Una actividad al 100 % pasa a revisión del líder del proyecto, quien la aprueba o la rechaza indicando el motivo. Si se rechaza, EL COLABORADOR corrige y vuelve a presentar.",
                "4. PLAZOS\nLos plazos de cada actividad son los definidos en el sistema al asignarla. Los retrasos deben comunicarse al líder del proyecto con anticipación.",
                "5. OBLIGACIONES DEL COLABORADOR\nCumplir los estándares de calidad, mantener la confidencialidad de la información, usar los activos digitales solo para fines profesionales y comunicar oportunamente impedimentos o riesgos.",
                "6. OBLIGACIONES DE LA EMPRESA\nAsignar actividades con alcance claro, revisar las evidencias en un plazo razonable y mantener informado al colaborador sobre el resultado de las revisiones.",
                "7. EVALUACIÓN\nEl cumplimiento se mide por el avance de las actividades, la calidad de la evidencia presentada y el resultado de la revisión del líder.",
                "8. FALTAS Y SANCIONES\nLas faltas y sus sanciones son las establecidas en el Manual de Faltas y Sanciones que acompaña a este documento y forma parte integrante de él.",
                "9. ACEPTACIÓN\nCon su firma, EL COLABORADOR declara haber leído y aceptado estos términos de referencia y el Manual de Faltas y Sanciones en la versión indicada en este documento.",
            ]),
            'version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropColumn(['normativa_version_aceptada', 'normativa_aceptada_en']);
        });

        Schema::dropIfExists('terminos_referencia');
        Schema::dropIfExists('faltas');
    }
};
