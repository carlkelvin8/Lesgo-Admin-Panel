<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTopUp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentWalletIndexTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'admin_role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer', 'name' => 'Juan Dela Cruz']);
    }

    public function test_payments_index_renders_existing_payments(): void
    {
        $this->actingAs($this->admin());

        $customer = $this->customer();
        $partner = Partner::factory()->create();
        $service = Service::create(['code' => 'delivery', 'name' => 'Delivery', 'base_fare' => 50]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'partner_id' => $partner->id,
            'service_id' => $service->id,
            'status' => 'completed',
            'payment_status' => 'paid',
            'actual_fare' => 250.00,
        ]);
        Payment::create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'partner_id' => $partner->id,
            'amount' => 250.00,
            'method' => 'card',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $response = $this->get(route('admin.payments.index'));

        $response->assertOk();
        $response->assertSee('Juan Dela Cruz');
        $response->assertSee('250.00');
    }

    public function test_wallets_index_renders_existing_wallets(): void
    {
        $this->actingAs($this->admin());

        $user = $this->customer();
        Wallet::create([
            'user_id' => $user->id,
            'balance' => 500.00,
            'currency' => 'PHP',
        ]);

        $response = $this->get(route('admin.wallets.index'));

        $response->assertOk();
        $response->assertSee('Juan Dela Cruz');
        $response->assertSee('500.00');
    }

    public function test_wallet_top_ups_index_renders_existing_topups(): void
    {
        $this->actingAs($this->admin());

        $user = $this->customer();
        $wallet = Wallet::create([
            'user_id' => $user->id,
            'balance' => 0.00,
            'currency' => 'PHP',
        ]);
        WalletTopUp::create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'amount' => 300.00,
            'fee' => 15.00,
            'total_charged' => 315.00,
            'status' => 'pending',
            'provider' => 'paymongo',
            'external_id' => 'topup-test-001',
        ]);

        $response = $this->get(route('admin.wallets.top-ups.index'));

        $response->assertOk();
        $response->assertSee('Juan Dela Cruz');
    }
}