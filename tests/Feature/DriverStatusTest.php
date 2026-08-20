<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\DriverProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DriverStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_activate_an_inactive_driver(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_role' => 'super_admin',
            'is_active' => true,
        ]);
        $driverUser = User::factory()->create([
            'role' => 'driver',
            'is_active' => true,
        ]);
        $driver = DriverProfile::query()->create([
            'user_id' => $driverUser->id,
            'status' => 'inactive',
        ]);

        $this->actingAs($admin)
            ->followingRedirects()
            ->post(route('admin.drivers.toggle', $driver))
            ->assertOk()
            ->assertSee('Driver active successfully.')
            ->assertSee('Active');

        $this->assertSame('active', $driver->fresh()->status);
    }

    public function test_driver_status_page_uses_config_fallback_while_role_migration_is_pending(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_role' => 'super_admin',
            'is_active' => true,
        ]);
        $driverUser = User::factory()->create([
            'role' => 'driver',
            'is_active' => true,
        ]);
        $driver = DriverProfile::query()->create([
            'user_id' => $driverUser->id,
            'status' => 'inactive',
        ]);

        AdminRole::forgetDefinitionCache();
        Schema::drop('admin_access_roles');

        $this->actingAs($admin)
            ->followingRedirects()
            ->post(route('admin.drivers.toggle', $driver))
            ->assertOk()
            ->assertSee('Driver active successfully.');

        $this->assertSame('active', $driver->fresh()->status);
    }
}
