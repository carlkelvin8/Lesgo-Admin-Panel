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

    public function test_super_admin_can_change_driver_status_from_the_edit_form(): void
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
            'status' => 'pending',
            'license_number' => 'LIC-123',
            'vehicle_type' => 'Motorcycle',
            'plate_number' => 'ABC-1234',
            'package_tier' => 'basic',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.drivers.update', $driver), [
                'status' => 'active',
                'license_number' => 'LIC-123',
                'vehicle_type' => 'Motorcycle',
                'vehicle_make' => null,
                'vehicle_model' => null,
                'vehicle_color' => null,
                'vehicle_plate_number' => null,
                'package_tier' => 'basic',
            ])
            ->assertRedirect(route('admin.drivers.show', $driver));

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

    public function test_driver_pages_render_cloud_registration_images(): void
    {
        $this->withoutVite();

        config()->set('filesystems.disks.s3.url', 'https://media.example.test');
        config()->set('filesystems.disks.s3.key', null);
        config()->set('filesystems.disks.s3.secret', null);

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
            'status' => 'pending',
            'id_document_path' => 'registrations/drivers/example/drivers_license.jpg',
            'documents' => [
                'selfie_path' => 'registrations/drivers/example/selfie.jpg',
                'motorcycle_orcr_path' => 'registrations/drivers/example/orcr.jpg',
            ],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.drivers.index'))
            ->assertOk()
            ->assertSee('https://media.example.test/registrations/drivers/example/selfie.jpg', false);

        $this->actingAs($admin)
            ->get(route('admin.drivers.show', $driver))
            ->assertOk()
            ->assertSee('https://media.example.test/registrations/drivers/example/drivers_license.jpg', false)
            ->assertSee('https://media.example.test/registrations/drivers/example/orcr.jpg', false);
    }
}
