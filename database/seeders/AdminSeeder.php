<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('admin.seed_account.email');
        $name = config('admin.seed_account.name');
        $adminRole = config('admin.seed_account.role');
        $password = config('admin.seed_account.password');
        $admin = User::withTrashed()->where('email', $email)->first();

        if (! array_key_exists($adminRole, config('admin.roles', []))) {
            throw ValidationException::withMessages([
                'ADMIN_ROLE' => "Unknown administrator access level: {$adminRole}",
            ]);
        }

        if (app()->environment('production') && blank($password) && ! $admin) {
            $this->command->warn('Admin account was not seeded. Set ADMIN_PASSWORD in the production environment first.');

            return;
        }

        $admin ??= new User(['email' => $email]);

        $admin->forceFill([
            'name' => $name,
            'role' => 'admin',
            'admin_role' => $adminRole,
            'is_active' => true,
            'email_verified_at' => $admin->email_verified_at ?? now(),
        ]);

        if (filled($password)) {
            $admin->password = Hash::make($password);
        } elseif (! $admin->exists) {
            $admin->password = Hash::make('password');
        }

        $admin->save();

        if ($admin->trashed()) {
            $admin->restore();
        }

        $this->command->info("Administrator account ready: {$email} ({$adminRole})");
    }
}
