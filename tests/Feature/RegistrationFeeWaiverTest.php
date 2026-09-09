<?php

namespace Tests\Feature;

use App\Models\DriverProfile;
use App\Models\RegistrationFeePayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationFeeWaiverTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_waiver_approves_and_activates_a_pending_rider(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_role' => 'super_admin',
            'is_active' => true,
        ]);
        $rider = User::factory()->create([
            'role' => 'driver',
            'is_active' => true,
        ]);
        $profile = DriverProfile::query()->create([
            'user_id' => $rider->id,
            'status' => 'pending',
        ]);
        $fee = RegistrationFeePayment::query()->create([
            'user_id' => $rider->id,
            'account_type' => RegistrationFeePayment::TYPE_RIDER,
            'amount' => 500,
            'currency' => 'PHP',
            'idempotency_key' => 'test-rider-waiver-'.$rider->id,
            'payment_status' => RegistrationFeePayment::PAYMENT_UNPAID,
            'application_status' => RegistrationFeePayment::APP_PENDING,
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.registration-fees.waive', $fee), ['reason' => 'Operations approval'])
            ->assertSessionHas('success', 'Registration fee waived; application approved and account activated.');

        $fee->refresh();
        $this->assertSame(RegistrationFeePayment::PAYMENT_WAIVED, $fee->payment_status);
        $this->assertSame(RegistrationFeePayment::APP_APPROVED, $fee->application_status);
        $this->assertTrue($fee->is_active);
        $this->assertSame($admin->id, $fee->waived_by);
        $this->assertSame($admin->id, $fee->approved_by);
        $this->assertSame('active', $profile->fresh()->status);
    }

    public function test_admin_can_configure_all_rider_package_prices(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_role' => 'super_admin',
            'is_active' => true,
        ]);
        $rider = User::factory()->create(['role' => 'driver', 'is_active' => true]);
        DriverProfile::query()->create([
            'user_id' => $rider->id,
            'status' => 'pending',
            'package_tier' => 'advance',
        ]);
        $fee = RegistrationFeePayment::query()->create([
            'user_id' => $rider->id,
            'account_type' => RegistrationFeePayment::TYPE_RIDER,
            'amount' => 500,
            'currency' => 'PHP',
            'idempotency_key' => 'test-rider-price-'.$rider->id,
            'payment_status' => RegistrationFeePayment::PAYMENT_UNPAID,
            'application_status' => RegistrationFeePayment::APP_PENDING,
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.registration-fees.rider-prices.update'), [
                'basic' => 1099,
                'advance' => 2099,
                'elite' => 3099,
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('security_settings', ['setting_key' => 'rider.package.price.basic', 'setting_value' => '1099.00']);
        $this->assertDatabaseHas('security_settings', ['setting_key' => 'rider.package.price.advance', 'setting_value' => '2099.00']);
        $this->assertDatabaseHas('security_settings', ['setting_key' => 'rider.package.price.pro', 'setting_value' => '3099.00']);
        $this->assertSame(2099.0, (float) $fee->fresh()->amount);
    }
}
