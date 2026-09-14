<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'review', 'blocked', 'completed', 'cancelled'])->default('pending');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->unsignedTinyInteger('progress_percentage')->default(0);
            $table->decimal('estimated_hours', 6, 2)->nullable();
            $table->decimal('actual_hours', 6, 2)->nullable();
            $table->text('blocked_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('project_id');
            $table->index('assigned_to');
            $table->index('status');
            $table->index('priority');
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
