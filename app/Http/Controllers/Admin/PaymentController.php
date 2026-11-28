<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReconcilePaymentRequest;
use App\Http\Requests\Admin\RecordRefundRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Support\CsvFormatter;
use App\Traits\SearchEscaping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    use SearchEscaping;

    private function buildPaymentQuery(Request $request)
    {
        $payments = Payment::query()->select('id', 'created_at')->selectRaw("'payment' as source");
        $orders = Order::query()
            ->select('id', 'created_at')->selectRaw("'order' as source")
            ->whereNotNull('payment_status')->where('payment_status', '!=', '')
            ->whereDoesntHave('payments');

        foreach ([[$payments, 'status', 'method'], [$orders, 'payment_status', 'payment_method']] as [$query, $status, $method]) {
            if ($request->filled('search')) {
                $pattern = '%'.$this->escapeLikePattern($request->string('search')->toString()).'%';
                $query->whereHas('customer', fn ($customer) => $customer
                    ->whereRaw($this->searchLikeSql('name'), [$pattern])
                    ->orWhereRaw($this->searchLikeSql('email'), [$pattern]));
            }
            if ($request->filled('status')) {
                $query->where($status, $request->input('status'));
            }
            if ($request->filled('method')) {
                $query->where($method, $request->input('method'));
            }
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->input('date_from'));
            }
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->input('date_to'));
            }
        }

        return DB::query()->fromSub($payments->toBase()->unionAll($orders->toBase()), 'payment_rows')
            ->orderByDesc('created_at')->orderByDesc('id')->orderBy('source');
    }

    private function hydrateRows($rows)
    {
        $payments = Payment::with('customer')->whereKey($rows->where('source', 'payment')->pluck('id')->all())
            ->get()->keyBy('id');
        $orders = Order::with('customer')->whereKey($rows->where('source', 'order')->pluck('id')->all())
            ->get()->keyBy('id');

        return $rows->map(function ($row) use ($payments, $orders) {
            if ($row->source === 'payment') {
                return $payments->get($row->id);
            }

            $order = $orders->get($row->id);

            return $order ? $this->orderToPayment($order, 'order-'.$row->id) : null;
        })->filter()->values();
    }

    private function orderToPayment(Order $order, string $reference)
    {
        return new class($order, $reference)
        {
            public $id;

            public string $routeReference;

            public $order_id;

            public $customer;

            public $amount;

            public $currency;

            public $method;

            public $status;

            public $paid_at;

            public $refunded_amount = 0;

            public $provider;

            public $provider_reference;

            public $meta;

            public $reconciliation_status = 'unreconciled';

            public $reconciliation_notes;

            public $created_at;

            public $reconciler = null;

            public function getRouteKey()
            {
                return $this->routeReference;
            }

            public function __construct(Order $order, string $reference)
            {
                $this->id = $order->id;
                $this->routeReference = $reference;
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
        $payments = $this->buildPaymentQuery($request)->paginate(20)->withQueryString();
        $payments->setCollection($this->hydrateRows($payments->getCollection()));
        $methods = Payment::query()->whereNotNull('method')->distinct()->pluck('method')
            ->merge(Order::query()->whereNotNull('payment_method')->distinct()->pluck('payment_method'))
            ->filter()->unique()->sort()->values();

        return view('admin.payments.index', compact('payments', 'methods'));
    }

    private function orderPaymentId(string $reference): string
    {
        $id = str_starts_with($reference, 'order-') ? substr($reference, 6) : $reference;
        abort_unless(ctype_digit($id), 404);

        return $id;
    }

    public function show(string $payment)
    {
        $id = $this->orderPaymentId($payment);
        $paymentModel = str_starts_with($payment, 'order-') ? null : Payment::with(['customer', 'order', 'reconciler'])->find($id);

        if (! $paymentModel) {
            $order = Order::with('customer')->whereDoesntHave('payments')->findOrFail($id);
            $paymentModel = $this->orderToPayment($order, $payment);
        }

        return view('admin.payments.show', ['payment' => $paymentModel]);
    }

    public function recordRefund(RecordRefundRequest $request, string $payment)
    {
        $validated = $request->validated();

        $id = $this->orderPaymentId($payment);
        $paymentModel = str_starts_with($payment, 'order-') ? null : Payment::find($id);
        if (! $paymentModel) {
            DB::transaction(function () use ($id, $validated) {
                $order = Order::whereDoesntHave('payments')->lockForUpdate()->findOrFail($id);

                if ($order->payment_status !== 'paid') {
                    throw ValidationException::withMessages(['amount' => 'Only paid payments can receive a refund record.']);
                }

                $recordedFare = round((float) ($order->actual_fare ?? $order->estimated_fare), 2);
                if (round((float) $validated['amount'], 2) !== $recordedFare) {
                    throw ValidationException::withMessages(['amount' => 'Enter the full recorded fare to refund a payment tracked on its order.']);
                }

                $order->update(['payment_status' => 'refunded']);
            });

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

        $id = $this->orderPaymentId($payment);
        $paymentModel = str_starts_with($payment, 'order-') ? null : Payment::find($id);
        if (! $paymentModel) {
            return back()->withErrors(['payment' => 'A payment record is required for reconciliation.']);
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
        $query = $this->buildPaymentQuery($request);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="payments_export_'.now()->format('Y-m-d_His').'.csv"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ];

        $callback = function () use ($query) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Payment ID', 'Customer', 'Order ID', 'Amount', 'Method', 'Status', 'Paid Date']);

            $query->chunk(500, function ($rows) use ($file) {
                foreach ($this->hydrateRows($rows) as $payment) {
                    fputcsv($file, [
                        $payment->id,
                        CsvFormatter::cell($payment->customer?->name ?? 'N/A'),
                        $payment->order_id,
                        $payment->amount,
                        CsvFormatter::cell($payment->method ?? 'N/A'),
                        CsvFormatter::cell($payment->status),
                        $payment->paid_at?->format('Y-m-d H:i:s') ?? '',
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
