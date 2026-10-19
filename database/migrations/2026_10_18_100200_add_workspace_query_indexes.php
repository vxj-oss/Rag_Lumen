<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tareas', fn (Blueprint $t) => $t->index(['asignado_a', 'estado_id', 'fecha_vencimiento'], 'tasks_assignment_state_due'));
        Schema::table('tareas', fn (Blueprint $t) => $t->index(['proyecto_id', 'tarea_padre_id', 'estado_id'], 'tasks_project_parent_state'));
        Schema::table('documentos_rag', fn (Blueprint $t) => $t->index(['proyecto_id', 'estado'], 'rag_project_status'));
        Schema::table('conversaciones_rag', fn (Blueprint $t) => $t->index(['usuario_id', 'updated_at'], 'conversation_user_recent'));
    }

    public function down(): void
    {
        Schema::table('tareas', fn (Blueprint $t) => $t->dropIndex('tasks_assignment_state_due'));
        Schema::table('tareas', fn (Blueprint $t) => $t->dropIndex('tasks_project_parent_state'));
        Schema::table('documentos_rag', fn (Blueprint $t) => $t->dropIndex('rag_project_status'));
        Schema::table('conversaciones_rag', fn (Blueprint $t) => $t->dropIndex('conversation_user_recent'));
    }
};
