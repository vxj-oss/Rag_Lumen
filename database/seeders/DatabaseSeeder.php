<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\Enums\RoleName;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $admin = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $admin->assignRole(RoleName::Administrator->value);

        $this->call(DemoDataSeeder::class);
    }
}
