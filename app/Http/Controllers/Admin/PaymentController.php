<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Traits\SearchEscaping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    use SearchEscaping;
    public function index(Request $request)
    {
        $query = Payment::with(['customer', 'order']);

        if ($request->filled('search')) {
            $search = $this->escapeLikePattern($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                    ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('customer', fn ($cq) => $cq->where('email', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('method')) {
            $query->where('method', $request->method);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to.' 23:59:59');
        }

        $payments = $query->latest()->paginate(20)->withQueryString();

        return view('admin.payments.index', compact('payments'));
    }

    public function show(Payment $payment)
    {
        $payment->load(['customer', 'order', 'reconciler']);

        return view('admin.payments.show', compact('payment'));
    }

    public function recordRefund(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        DB::transaction(function () use ($payment, $validated) {
            $lockedPayment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($lockedPayment->status !== 'paid') {
                throw ValidationException::withMessages(['amount' => 'Only paid payments can receive a refund record.']);
            }

            $remaining = round((float) $lockedPayment->amount - (float) $lockedPayment->refunded_amount, 2);
            $refundAmount = round((float) $validated['amount'], 2);

            if ($refundAmount > $remaining) {
                throw ValidationException::withMessages(['amount' => 'Refund amount exceeds the remaining refundable balance.']);
            }

            $newRefundedAmount = round((float) $lockedPayment->refunded_amount + $refundAmount, 2);
            $isFullRefund = $newRefundedAmount >= (float) $lockedPayment->amount;

            $lockedPayment->update([
                'status' => $isFullRefund ? 'refunded' : 'paid',
                'refund_status' => $isFullRefund ? 'full' : 'partial',
                'refunded_amount' => $newRefundedAmount,
                'refund_reason' => $validated['reason'],
                'refunded_at' => now(),
            ]);

            $lockedPayment->order?->update([
                'payment_status' => $isFullRefund ? 'refunded' : 'paid',
            ]);
        });

        return back()->with('success', 'Refund record saved. Complete the provider-side refund using its reference before reconciling.');
    }

    public function reconcile(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'reconciliation_status' => ['required', 'in:matched,discrepancy,needs_review'],
            'reconciliation_notes' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $payment->update([
            ...$validated,
            'reconciled_at' => now(),
            'reconciled_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Payment reconciliation status updated.');
    }
}
