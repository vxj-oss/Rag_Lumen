<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 150)->unique();
            $table->string('phone', 30)->nullable();
            $table->string('position', 100)->nullable();
            $table->enum('specialty', [
                'backend',
                'frontend',
                'fullstack',
                'ux_ui',
                'graphic_design',
                'marketing',
                'seo',
                'advertising',
                'project_manager',
                'other',
            ]);
            $table->enum('status', ['active', 'inactive', 'on_leave'])->default('active');
            $table->date('hire_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('specialty');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
