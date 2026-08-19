<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@lesgo.com');
        $password = env('ADMIN_PASSWORD');

        if (app()->environment('production') && blank($password)) {
            $this->command->warn('Admin account was not seeded. Set ADMIN_PASSWORD in the production environment first.');

            return;
        }

        $password ??= 'password';

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'LesGo Admin',
                'password' => Hash::make($password),
                'role' => 'admin',
                'admin_role' => 'super_admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info("Admin user created: {$email}");
    }
}
