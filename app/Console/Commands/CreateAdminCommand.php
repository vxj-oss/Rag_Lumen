<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Enums\RoleName;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateAdminCommand extends Command
{
    protected $signature = 'app:create-admin
        {--name= : Nombre completo}
        {--email= : Correo electrónico}
        {--password= : Contraseña (si se omite, se pide de forma interactiva y oculta)}';

    protected $description = 'Crea la primera cuenta de administrador (correr una sola vez tras desplegar)';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Nombre completo');
        $email = $this->option('email') ?: $this->ask('Correo electrónico');

        if ($this->option('password')) {
            $password = $this->option('password');
        } else {
            $password = $this->secret('Contraseña (mínimo 8 caracteres)');
            $confirmation = $this->secret('Confirma la contraseña');

            if ($password !== $confirmation) {
                $this->error('Las contraseñas no coinciden.');

                return self::FAILURE;
            }
        }

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
            ],
            [
                'name.required' => 'El nombre es obligatorio.',
                'name.max' => 'El nombre no puede superar los 255 caracteres.',
                'email.required' => 'El correo es obligatorio.',
                'email.email' => 'El correo no es válido.',
                'email.max' => 'El correo no puede superar los 255 caracteres.',
                'email.unique' => 'Ya existe un usuario con ese correo.',
                'password.required' => 'La contraseña es obligatoria.',
                'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);

        $user->email_verified_at = now();
        $user->save();

        $user->assignRole(RoleName::Administrator->value);

        $this->info("Administrador creado correctamente: {$email}");

        return self::SUCCESS;
    }
}
