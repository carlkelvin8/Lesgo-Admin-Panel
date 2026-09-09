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
}
