<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('from_status_id')->nullable()->constrained('task_statuses')->nullOnDelete();
            $table->foreignId('to_status_id')->nullable()->constrained('task_statuses')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('task_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_status_history');
    }
};
