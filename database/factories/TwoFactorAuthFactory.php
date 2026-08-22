<?php

namespace Database\Factories;

use App\Models\TwoFactorAuth;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TwoFactorAuthFactory extends Factory
{
    protected $model = TwoFactorAuth::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'method' => 'totp',
            'secret' => null,
            'backup_codes' => null,
            'phone_number' => null,
            'is_enabled' => false,
            'enabled_at' => null,
            'last_used_at' => null,
            'recovery_codes' => null,
            'metadata' => null,
        ];
    }
}
