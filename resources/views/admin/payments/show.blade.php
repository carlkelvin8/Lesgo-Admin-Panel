@extends('admin.layouts.app')
@section('title', 'Payment Details - LesGo Admin')
@section('header', 'Payment #' . $payment->id)

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Payment Information</h3>
        @php
            $sc = [
                'pending' => 'bg-yellow-100 text-yellow-800',
                'paid' => 'bg-green-100 text-green-800',
                'failed' => 'bg-red-100 text-red-800',
                'refunded' => 'bg-blue-100 text-blue-800',
            ];
        @endphp
        <div class="space-y-3 text-sm">
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">ID</span><span>#{{ $payment->id }}</span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Amount</span><span class="font-semibold text-lg">₱{{ number_format($payment->amount, 2) }}</span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Currency</span><span>{{ $payment->currency }}</span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Method</span><span>{{ ucfirst($payment->method ?? 'N/A') }}</span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Status</span><span class="px-2 py-1 rounded-full text-xs font-medium {{ $sc[$payment->status] ?? 'bg-gray-100 text-gray-800' }}">{{ ucfirst($payment->status) }}</span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Provider</span><span>{{ $payment->provider ?? 'N/A' }}</span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Reference</span><span>{{ $payment->provider_reference ?? 'N/A' }}</span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Paid At</span><span>{{ $payment->paid_at ? $payment->paid_at->format('M d, Y H:i') : '—' }}</span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Refunded</span><span>₱{{ number_format($payment->refunded_amount, 2) }} / ₱{{ number_format($payment->amount, 2) }}</span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Reconciliation</span><span>{{ ucfirst(str_replace('_', ' ', $payment->reconciliation_status ?? 'unreconciled')) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Created</span><span>{{ $payment->created_at->format('M d, Y H:i') }}</span></div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Related Info</h3>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Customer</span><span>{{ $payment->customer->name ?? 'N/A' }}</span></div>
                <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Email</span><span>{{ $payment->customer->email ?? 'N/A' }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Order</span><span class="text-blue-600">#{{ $payment->order_id ?? 'N/A' }}</span></div>
            </div>
        </div>

        @if($payment->meta && count($payment->meta) > 0)
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Meta Data</h3>
                <div class="bg-gray-50 rounded-lg p-4">
                    <pre class="text-xs text-gray-700 whitespace-pre-wrap">{{ json_encode($payment->meta, JSON_PRETTY_PRINT) }}</pre>
                </div>
            </div>
        @endif

        @if(auth()->user()->hasAdminPermission('payments.manage'))
            @if($payment->status === 'paid')
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-semibold text-gray-800">Record Provider Refund</h3>
                <p class="mt-1 text-xs text-amber-700">This records an externally completed refund; it does not call the payment gateway.</p>
                <form method="POST" action="{{ route('admin.payments.refund', $payment) }}" class="mt-4 space-y-3" onsubmit="return confirm('Record this refund amount?')">@csrf
                    <div><label class="mb-1 block text-xs font-medium">Refund amount</label><input type="number" name="amount" min="0.01" max="{{ max(0, (float) $payment->amount - (float) $payment->refunded_amount) }}" step="0.01" required class="w-full rounded-lg border px-3 py-2"></div>
                    <div><label class="mb-1 block text-xs font-medium">Reason</label><textarea name="reason" rows="3" minlength="10" required class="w-full rounded-lg border px-3 py-2"></textarea></div>
                    <button class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white">Record refund</button>
                </form>
            </div>
            @endif

            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-semibold text-gray-800">Reconcile Payment</h3>
                <form method="POST" action="{{ route('admin.payments.reconcile', $payment) }}" class="mt-4 space-y-3">@csrf
                    <div><label class="mb-1 block text-xs font-medium">Result</label><select name="reconciliation_status" required class="w-full rounded-lg border px-3 py-2">@foreach(['matched'=>'Matched','discrepancy'=>'Discrepancy','needs_review'=>'Needs review'] as $value => $label)<option value="{{ $value }}" @selected($payment->reconciliation_status === $value)>{{ $label }}</option>@endforeach</select></div>
                    <div><label class="mb-1 block text-xs font-medium">Notes</label><textarea name="reconciliation_notes" rows="3" minlength="5" required class="w-full rounded-lg border px-3 py-2">{{ $payment->reconciliation_notes }}</textarea></div>
                    <button class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white">Save reconciliation</button>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
