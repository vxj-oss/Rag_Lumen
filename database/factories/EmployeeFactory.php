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
            'user_id' => null,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'position' => fake()->jobTitle(),
            'specialty' => fake()->randomElement(EmployeeSpecialty::cases())->value,
            'status' => EmployeeStatus::Active->value,
            'hire_date' => fake()->dateTimeBetween('-3 years', 'now')->format('Y-m-d'),
        ];
    }
}
