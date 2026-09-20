<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained('projects')->cascadeOnDelete();
            $table->string('name', 60);
            $table->string('slug', 60);
            $table->string('color', 20)->default('gray');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_initial')->default(false);
            $table->boolean('is_final')->default(false);
            $table->boolean('is_blocking')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['project_id', 'slug']);
            $table->index('project_id');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('status_id')->nullable()->after('status')->constrained('task_statuses')->nullOnDelete();
            $table->string('code', 60)->nullable()->unique()->after('id');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedInteger('task_counter')->default(0)->after('budget');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('task_counter');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('status_id');
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });

        Schema::dropIfExists('task_statuses');
    }
};
