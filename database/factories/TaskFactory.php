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
            'proyecto_id' => Project::factory(),
            'asignado_a' => Employee::inRandomOrder()->value('id'),
            'creado_por' => null,
            'titulo' => fake()->sentence(6),
            'descripcion' => fake()->paragraph(),
            'estado' => fake()->randomElement(TaskStatus::cases())->value,
            'prioridad' => fake()->randomElement(Priority::cases())->value,
            'fecha_inicio' => $startDate->format('Y-m-d'),
            'fecha_vencimiento' => fake()->dateTimeBetween($startDate, '+2 months')->format('Y-m-d'),
            'porcentaje_progreso' => fake()->numberBetween(0, 100),
            'horas_estimadas' => fake()->randomFloat(2, 1, 80),
            'horas_reales' => null,
        ];
    }
}
