<?php

namespace Database\Factories;

use App\Models\Partner;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PartnerFactory extends Factory
{
    protected $model = Partner::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'user_id'                    => User::factory()->create(['role' => 'partner_admin'])->id,
            'name'                       => $name,
            'slug'                       => Str::slug($name) . '-' . fake()->unique()->randomNumber(4),
            'description'                => fake()->sentence(),
            'category'                   => fake()->randomElement(['restaurant', 'grocery', 'pharmacy']),
            'status'                     => 'active',
            'delivery_fee'               => fake()->randomFloat(2, 30, 100),
            'is_open'                    => true,
            'rating'                     => fake()->randomFloat(1, 3.5, 5.0),
        ];
    }
}
