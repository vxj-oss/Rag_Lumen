<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Support\Enums\EmployeeSpecialty;
use App\Support\Enums\EmployeeStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{

    public function definition(): array
    {
        return [
            'usuario_id' => null,
            'nombres' => fake()->firstName(),
            'apellidos' => fake()->lastName(),
            'correo' => fake()->unique()->safeEmail(),
            'telefono' => fake()->phoneNumber(),
            'cargo' => fake()->jobTitle(),
            'especialidad' => fake()->randomElement(EmployeeSpecialty::cases())->value,
            'estado' => EmployeeStatus::Active->value,
            'fecha_contratacion' => fake()->dateTimeBetween('-3 years', 'now')->format('Y-m-d'),
        ];
    }
}
