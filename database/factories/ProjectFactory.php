<?php

namespace Database\Factories;

use App\Models\Employee;
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
            'codigo' => strtoupper(fake()->unique()->bothify('PROJ-###')),
            'nombre' => fake()->catchPhrase(),
            'descripcion' => fake()->paragraph(),
            'tipo' => fake()->randomElement(ProjectType::cases())->value,
            'cliente_id' => null,
            'fecha_inicio' => $startDate->format('Y-m-d'),
            'fecha_fin_estimada' => fake()->dateTimeBetween($startDate, '+6 months')->format('Y-m-d'),
            'fecha_fin_real' => null,
            'estado' => fake()->randomElement(ProjectStatus::cases())->value,
            'prioridad' => fake()->randomElement(Priority::cases())->value,
            'empleado_responsable_id' => Employee::inRandomOrder()->value('id'),
            'presupuesto' => fake()->randomFloat(2, 1000, 50000),
            'observaciones' => null,
        ];
    }
}
