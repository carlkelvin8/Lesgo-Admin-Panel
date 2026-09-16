<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentsIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_payments_index_renders_payment_data(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_role' => 'super_admin',
            'is_active' => true,
        ]);

        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create();
        Payment::factory()->create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'amount' => 250.00,
            'method' => 'cash',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.payments.index'))
            ->assertOk()
            ->assertSee('#'.$order->id)
            ->assertSee('250.00');
    }

    public function test_payments_index_with_no_data_shows_empty_state(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.payments.index'))
            ->assertOk()
            ->assertSee('No payments found');
    }
}