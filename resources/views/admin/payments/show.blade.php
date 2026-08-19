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
    </div>
</div>
@endsection
