<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_account_can_be_repaired_without_resetting_its_password(): void
    {
        $admin = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'seanlestersuniga@gmail.com',
            'password' => Hash::make('ExistingPassword123'),
            'role' => 'customer',
            'admin_role' => null,
            'is_active' => false,
        ]);

        config()->set('admin.seed_account', [
            'email' => 'seanlestersuniga@gmail.com',
            'name' => 'Sean Lester Suniga',
            'role' => 'operations',
            'password' => null,
        ]);

        $this->seed(AdminSeeder::class);

        $admin->refresh();

        $this->assertSame('Sean Lester Suniga', $admin->name);
        $this->assertSame('admin', $admin->role);
        $this->assertSame('operations', $admin->admin_role);
        $this->assertTrue($admin->is_active);
        $this->assertTrue(Hash::check('ExistingPassword123', $admin->password));
    }
}
