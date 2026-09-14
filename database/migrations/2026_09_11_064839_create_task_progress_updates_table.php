<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('task_progress_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('previous_percentage');
            $table->unsignedTinyInteger('new_percentage');
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('task_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_progress_updates');
    }
};
