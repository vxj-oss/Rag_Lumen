<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Project;
use App\Support\Enums\Priority;
use App\Support\Enums\ProjectStatus;
use App\Support\Enums\ProjectType;
use Illuminate\Database\Eloquent\Factories\Factory;


class ProjectFactory extends Factory
{
    
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-6 months', 'now');

        return [
            'code' => strtoupper(fake()->unique()->bothify('PROJ-###')),
            'name' => fake()->catchPhrase(),
            'description' => fake()->paragraph(),
            'type' => fake()->randomElement(ProjectType::cases())->value,
            'client' => fake()->company(),
            'start_date' => $startDate->format('Y-m-d'),
            'estimated_end_date' => fake()->dateTimeBetween($startDate, '+6 months')->format('Y-m-d'),
            'actual_end_date' => null,
            'status' => fake()->randomElement(ProjectStatus::cases())->value,
            'priority' => fake()->randomElement(Priority::cases())->value,
            'responsible_employee_id' => Employee::inRandomOrder()->value('id'),
            'budget' => fake()->randomFloat(2, 1000, 50000),
            'observations' => null,
        ];
    }
}
