<?php

namespace Tests\Feature;

use App\Models\DriverProfile;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_a_rider_and_linked_user(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_role' => 'super_admin',
            'is_active' => true,
        ]);
        $driverUser = User::factory()->driver()->create();
        $driver = DriverProfile::factory()->create(['user_id' => $driverUser->id]);

        $this->actingAs($admin)
            ->get(route('admin.drivers.show', $driver))
            ->assertOk()
            ->assertSee('Delete Rider &amp; Data', false)
            ->assertSee(route('admin.drivers.destroy', $driver));

        $this->delete(route('admin.drivers.destroy', $driver))
            ->assertRedirect(route('admin.drivers.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('driver_profiles', ['id' => $driver->id]);
        $this->assertDatabaseMissing('users', ['id' => $driverUser->id]);
    }

    public function test_deleting_a_rider_also_deletes_linked_orders(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_role' => 'super_admin',
            'is_active' => true,
        ]);
        $driver = DriverProfile::factory()->create();
        $order = Order::factory()->create([
            'driver_id' => $driver->id,
            'service_id' => Service::factory(),
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.drivers.destroy', $driver))
            ->assertRedirect(route('admin.drivers.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertDatabaseMissing('driver_profiles', ['id' => $driver->id]);
    }

    public function test_admin_can_bulk_delete_riders(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_role' => 'super_admin',
            'is_active' => true,
        ]);
        $drivers = DriverProfile::factory()->count(2)->create();
        $userIds = $drivers->pluck('user_id');

        $this->actingAs($admin)
            ->delete(route('admin.drivers.bulk-destroy'), ['ids' => $drivers->pluck('id')->all()])
            ->assertSessionHas('success');

        foreach ($drivers as $driver) {
            $this->assertDatabaseMissing('driver_profiles', ['id' => $driver->id]);
        }
        foreach ($userIds as $userId) {
            $this->assertDatabaseMissing('users', ['id' => $userId]);
        }
    }
}
