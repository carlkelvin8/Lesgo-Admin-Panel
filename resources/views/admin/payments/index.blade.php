@extends('admin.layouts.app')
@section('title', 'Payments - LesGo Admin')
@section('header', 'Payments Management')

@section('content')
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <x-filter-panel action="{{ request()->url() }}">
        <x-filter-input name="search" label="Search" placeholder="Search by customer name, email..." value="{{ request('search') }}" />
        <x-filter-input name="status" label="Status" type="select" :options="['pending' => 'Pending', 'paid' => 'Paid', 'failed' => 'Failed', 'refunded' => 'Refunded']" />
        <x-filter-input name="method" label="Method" type="select" :options="['cash' => 'Cash', 'card' => 'Card', 'ewallet' => 'E-Wallet']" />
        <x-filter-input name="date_from" label="From" type="date" />
        <x-filter-input name="date_to" label="To" type="date" />
    </x-filter-panel>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="responsive-table w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">ID</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Customer</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Order</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Amount</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Method</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Status</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Paid Date</th>
                    <th class="text-right px-6 py-3 text-gray-500 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($payments as $payment)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-blue-600">#{{ $payment->id }}</td>
                        <td class="px-6 py-4 text-gray-700">{{ $payment->customer->name ?? 'N/A' }}</td>
                        <td class="px-6 py-4 text-gray-700">#{{ $payment->order_id ?? 'N/A' }}</td>
                        <td class="px-6 py-4 font-medium">₱{{ number_format($payment->amount, 2) }}</td>
                        <td class="px-6 py-4 text-gray-700">{{ ucfirst($payment->method ?? 'N/A') }}</td>
                        <td class="px-6 py-4">
                            <x-status-badge status="{{ $payment->status }}" />
                        </td>
                        <td class="px-6 py-4 text-gray-500 text-xs">{{ $payment->paid_at ? $payment->paid_at->format('M d, Y H:i') : '—' }}</td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.payments.show', $payment) }}" class="text-blue-600 hover:text-blue-800"><i class="fas fa-eye"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-empty-state icon="fa-credit-card" title="No payments found" description="There are no payments matching your criteria." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t">{{ $payments->links() }}</div>
</div>
@endsection
