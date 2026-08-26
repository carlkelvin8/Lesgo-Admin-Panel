<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function recordRefund(Payment $payment, float $amount, string $reason): void
    {
        DB::transaction(function () use ($payment, $amount, $reason) {
            $lockedPayment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($lockedPayment->status !== 'paid') {
                throw ValidationException::withMessages(['amount' => 'Only paid payments can receive a refund record.']);
            }

            $remaining = round((float) $lockedPayment->amount - (float) $lockedPayment->refunded_amount, 2);
            $refundAmount = round($amount, 2);

            if ($refundAmount > $remaining) {
                throw ValidationException::withMessages(['amount' => 'Refund amount exceeds the remaining refundable balance.']);
            }

            $newRefundedAmount = round((float) $lockedPayment->refunded_amount + $refundAmount, 2);
            $isFullRefund = $newRefundedAmount >= (float) $lockedPayment->amount;

            $lockedPayment->update([
                'status' => $isFullRefund ? 'refunded' : 'paid',
                'refund_status' => $isFullRefund ? 'full' : 'partial',
                'refunded_amount' => $newRefundedAmount,
                'refund_reason' => $reason,
                'refunded_at' => now(),
            ]);

            $lockedPayment->order?->update([
                'payment_status' => $isFullRefund ? 'refunded' : 'paid',
            ]);
        });
    }

    public function reconcile(Payment $payment, string $status, string $notes, int $adminId): void
    {
        $payment->update([
            'reconciliation_status' => $status,
            'reconciliation_notes' => $notes,
            'reconciled_at' => now(),
            'reconciled_by' => $adminId,
        ]);
    }
}
