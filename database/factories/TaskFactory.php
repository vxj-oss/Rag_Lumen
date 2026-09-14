<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Support\Enums\Priority;
use App\Support\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-2 months', 'now');

        return [
            'project_id' => Project::factory(),
            'assigned_to' => Employee::inRandomOrder()->value('id'),
            'created_by' => null,
            'title' => fake()->sentence(6),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(TaskStatus::cases())->value,
            'priority' => fake()->randomElement(Priority::cases())->value,
            'start_date' => $startDate->format('Y-m-d'),
            'due_date' => fake()->dateTimeBetween($startDate, '+2 months')->format('Y-m-d'),
            'progress_percentage' => fake()->numberBetween(0, 100),
            'estimated_hours' => fake()->randomFloat(2, 1, 80),
            'actual_hours' => null,
        ];
    }
}
