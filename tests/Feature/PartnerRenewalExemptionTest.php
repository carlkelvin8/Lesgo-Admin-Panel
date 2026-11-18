<?php

namespace Tests\Feature;

use App\Models\Partner;
use App\Models\RegistrationFeePayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerRenewalExemptionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'admin_role' => 'super_admin', 'is_active' => true]);
    }

    private function merchantFee(array $overrides = []): RegistrationFeePayment
    {
        $owner = User::factory()->create(['role' => 'partner_admin', 'is_active' => true]);

        return RegistrationFeePayment::query()->create(array_merge([
            'user_id' => $owner->id,
            'account_type' => RegistrationFeePayment::TYPE_MERCHANT,
            'amount' => 1000,
            'currency' => 'PHP',
            'idempotency_key' => 'test-merchant-exempt-'.$owner->id,
            'payment_status' => RegistrationFeePayment::PAYMENT_PAID,
            'payment_date' => now()->subYears(2),
            'application_status' => RegistrationFeePayment::APP_APPROVED,
            'is_grandfathered' => false,
        ], $overrides));
    }

    public function test_admin_can_exempt_a_paid_long_time_partner(): void
    {
        $admin = $this->admin();
        $fee = $this->merchantFee();

        $this->actingAs($admin)
            ->post(route('admin.registration-fees.exempt-renewal', $fee), ['reason' => 'Partner since 2022'])
            ->assertSessionHas('success');

        $fee->refresh();
        $this->assertTrue($fee->is_grandfathered);
        $this->assertSame(RegistrationFeePayment::PAYMENT_PAID, $fee->payment_status);
        $this->assertSame('Partner since 2022', $fee->metadata['renewal_exemption']['reason']);
        $this->assertSame($admin->id, $fee->metadata['renewal_exemption']['exempted_by']);
    }

    public function test_exempting_an_unpaid_partner_waives_the_fee_without_approving_them(): void
    {
        $fee = $this->merchantFee([
            'payment_status' => RegistrationFeePayment::PAYMENT_UNPAID,
            'payment_date' => null,
            'application_status' => RegistrationFeePayment::APP_PENDING,
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.registration-fees.exempt-renewal', $fee))
            ->assertSessionHas('success');

        $fee->refresh();
        $this->assertTrue($fee->is_grandfathered);
        $this->assertSame(RegistrationFeePayment::PAYMENT_WAIVED, $fee->payment_status);
        $this->assertSame(RegistrationFeePayment::APP_PENDING, $fee->application_status);
    }

    public function test_riders_cannot_be_exempted(): void
    {
        $rider = User::factory()->create(['role' => 'driver', 'is_active' => true]);
        $fee = RegistrationFeePayment::query()->create([
            'user_id' => $rider->id,
            'account_type' => RegistrationFeePayment::TYPE_RIDER,
            'amount' => 500,
            'currency' => 'PHP',
            'idempotency_key' => 'test-rider-exempt-'.$rider->id,
            'payment_status' => RegistrationFeePayment::PAYMENT_UNPAID,
            'application_status' => RegistrationFeePayment::APP_PENDING,
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.registration-fees.exempt-renewal', $fee))
            ->assertSessionHas('error');

        $this->assertFalse($fee->fresh()->is_grandfathered);
    }

    public function test_partner_page_offers_the_exemption_until_it_is_applied(): void
    {
        $this->withoutVite();
        $admin = $this->admin();
        $fee = $this->merchantFee();
        $partner = Partner::create([
            'user_id' => $fee->user_id,
            'name' => 'Exempt Test Partner',
            'slug' => 'exempt-test-partner',
            'status' => 'active',
        ]);

        $this->actingAs($admin)->get(route('admin.partners.show', $partner))
            ->assertOk()->assertSee('Exempt from Annual Renewal');

        $fee->update(['is_grandfathered' => true]);

        $this->actingAs($admin)->get(route('admin.partners.show', $partner))
            ->assertOk()->assertDontSee('Exempt from Annual Renewal');
    }
}
