<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->enum('type', [
                'software_development',
                'web_development',
                'ux_ui_design',
                'graphic_design',
                'digital_marketing',
                'seo',
                'advertising',
                'branding',
                'campaign',
                'other',
            ]);
            $table->string('client', 150)->nullable();
            $table->date('start_date');
            $table->date('estimated_end_date');
            $table->date('actual_end_date')->nullable();
            $table->enum('status', [
                'planning',
                'in_progress',
                'review',
                'blocked',
                'paused',
                'completed',
                'cancelled',
            ])->default('planning');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->foreignId('responsible_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->decimal('budget', 12, 2)->nullable();
            $table->unsignedTinyInteger('risk_score')->nullable();
            $table->enum('risk_level', ['low', 'medium', 'high', 'critical'])->nullable();
            $table->dateTime('risk_calculated_at')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('priority');
            $table->index('risk_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
