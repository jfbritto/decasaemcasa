<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Criar usuário admin com email verificado
        User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@decasaemcasa.com.br')],
            [
                'name' => 'Administrador',
                'password' => Hash::make(env('ADMIN_PASSWORD', 'admin123')),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Usuário administrador criado!');
        $this->command->info('Email: ' . env('ADMIN_EMAIL', 'admin@decasaemcasa.com.br'));
        $this->command->info('Senha: ' . env('ADMIN_PASSWORD', 'admin123'));
    }
}
