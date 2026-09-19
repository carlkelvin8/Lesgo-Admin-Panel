<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RecordRefundRequest;
use App\Http\Requests\Admin\ReconcilePaymentRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Traits\SearchEscaping;
use App\Support\CsvFormatter;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    use SearchEscaping;

    private function buildPaymentQuery(Request $request)
    {
        return Order::with(['customer'])
            ->whereNotNull('payment_status')
            ->where('payment_status', '!=', '')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $this->escapeLikePattern($request->search);
                $q->whereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('payment_status', $request->status))
            ->when($request->filled('method'), fn ($q) => $q->where('payment_method', $request->method))
            ->when($request->filled('date_from'), fn ($q) => $q->where('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->where('created_at', '<=', $request->date_to.' 23:59:59'));
    }

    private function orderToPayment($order)
    {
        return new class($order) {
            public $id, $order_id, $customer, $amount, $currency, $method, $status, $paid_at;
            public $refunded_amount = 0, $provider, $provider_reference, $meta;
            public $reconciliation_status = 'unreconciled', $reconciliation_notes, $created_at;
            public $reconciler = null;

            public function getRouteKey()
            {
                return $this->id;
            }

            public function __construct($order)
            {
                $this->id = $order->id;
                $this->order_id = $order->id;
                $this->customer = $order->customer;
                $this->amount = (float) ($order->actual_fare ?? $order->estimated_fare ?? 0);
                $this->currency = 'PHP';
                $this->method = $order->payment_method;
                $this->status = $order->payment_status;
                $this->paid_at = $order->completed_at;
                $this->provider = null;
                $this->provider_reference = null;
                $this->meta = null;
                $this->created_at = $order->created_at;
            }
        };
    }

    public function index(Request $request)
    {
        $orders = $this->buildPaymentQuery($request)->latest()->paginate(20)->withQueryString();

        $payments = $orders->getCollection()->map(fn ($o) => $this->orderToPayment($o));
        $orders->setCollection($payments);

        return view('admin.payments.index', ['payments' => $orders]);
    }

    public function show(string $payment)
    {
        $paymentModel = Payment::with(['customer', 'order', 'reconciler'])->find($payment);

        if (! $paymentModel) {
            $order = Order::with(['customer'])->findOrFail($payment);
            $paymentModel = $this->orderToPayment($order);
        }

        return view('admin.payments.show', ['payment' => $paymentModel]);
    }

    public function recordRefund(RecordRefundRequest $request, string $payment)
    {
        $validated = $request->validated();

        $paymentModel = Payment::find($payment);
        if (! $paymentModel) {
            $order = Order::findOrFail($payment);

            if ($order->payment_status !== 'paid') {
                throw ValidationException::withMessages(['amount' => 'Only paid payments can receive a refund record.']);
            }

            $order->update(['payment_status' => 'refunded']);
            return back()->with('success', 'Refund recorded on order.');
        }

        DB::transaction(function () use ($paymentModel, $validated) {
            $lockedPayment = Payment::whereKey($paymentModel->id)->lockForUpdate()->firstOrFail();

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

    public function reconcile(ReconcilePaymentRequest $request, string $payment)
    {
        $validated = $request->validated();

        $paymentModel = Payment::find($payment);
        if (! $paymentModel) {
            return back()->with('success', 'Reconciliation saved.');
        }

        $paymentModel->update([
            ...$validated,
            'reconciled_at' => now(),
            'reconciled_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Payment reconciliation status updated.');
    }

    public function export(Request $request)
    {
        $orders = $this->buildPaymentQuery($request)->latest()->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="payments_export_' . now()->format('Y-m-d_His') . '.csv"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ];

        $callback = function () use ($orders) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Payment ID', 'Customer', 'Order ID', 'Amount', 'Method', 'Status', 'Paid Date']);

            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->id,
                    CsvFormatter::cell($order->customer?->name ?? 'N/A'),
                    $order->id,
                    $order->actual_fare ?? $order->estimated_fare ?? 0,
                    CsvFormatter::cell($order->payment_method ?? 'N/A'),
                    CsvFormatter::cell($order->payment_status),
                    $order->completed_at ? $order->completed_at->format('Y-m-d H:i:s') : '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
