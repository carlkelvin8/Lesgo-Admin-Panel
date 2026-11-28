<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
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

        $order = Order::factory()->create([
            'payment_status' => 'paid',
            'actual_fare' => 250.00,
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

    public function test_payment_detail_renders_for_an_order_without_payment_record(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_role' => 'super_admin',
            'is_active' => true,
        ]);

        $order = Order::factory()->create([
            'payment_status' => 'paid',
            'actual_fare' => 250.00,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.payments.show', $order->id))
            ->assertOk()
            ->assertSee('Record Provider Refund')
            ->assertSee(route('admin.payments.refund', $order->id), false)
            ->assertSee(route('admin.payments.reconcile', $order->id), false);
    }

    public function test_order_only_payment_refunds_use_the_order_reference_and_require_the_full_amount(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_role' => 'super_admin',
            'is_active' => true,
        ]);
        $order = Order::factory()->create(['payment_status' => 'paid', 'actual_fare' => 250]);
        $payment = Payment::factory()->paid()->create(['id' => $order->id, 'amount' => 100]);
        $orderReference = 'order-'.$order->id;

        $this->actingAs($admin)->get(route('admin.payments.index'))
            ->assertOk()
            ->assertSee(route('admin.payments.show', $orderReference), false)
            ->assertSee(route('admin.payments.show', $payment), false);
        $this->get(route('admin.payments.show', $orderReference))->assertViewHas('payment', fn ($row) => $row->order_id === $order->id);
        $this->get(route('admin.payments.show', $payment))->assertViewHas('payment', fn ($row) => $row instanceof Payment && $row->is($payment));

        $reason = 'Provider confirmed refund.';
        $refundUrl = route('admin.payments.refund', $orderReference);
        $this->post($refundUrl, ['amount' => 100, 'reason' => $reason])->assertSessionHasErrors('amount');
        $this->assertSame('paid', $order->fresh()->payment_status);

        $this->post($refundUrl, ['amount' => 250, 'reason' => $reason])->assertSessionHas('success');
        $this->assertSame('refunded', $order->fresh()->payment_status);

        $this->post($refundUrl, ['amount' => 250, 'reason' => $reason])->assertSessionHasErrors('amount');
        $this->assertSame('paid', $payment->fresh()->status);
    }
}
