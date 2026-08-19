@extends('admin.layouts.app')
@section('title', 'Payments - LesGo Admin')
@section('header', 'Payments Management')

@section('content')
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[200px]">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by customer name, email..."
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-blue-500 outline-none">
        </div>
        <div>
            <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">All Status</option>
                @foreach(['pending','paid','failed','refunded'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <select name="method" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">All Methods</option>
                @foreach(['cash','card','ewallet'] as $m)
                    <option value="{{ $m }}" {{ request('method') === $m ? 'selected' : '' }}>{{ ucfirst($m) }}</option>
                @endforeach
            </select>
        </div>
        <div><input type="date" name="date_from" value="{{ request('date_from') }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="From"></div>
        <div><input type="date" name="date_to" value="{{ request('date_to') }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="To"></div>
        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-700"><i class="fas fa-filter mr-1"></i> Filter</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
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
                            @php
                                $sc = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'paid' => 'bg-green-100 text-green-800',
                                    'failed' => 'bg-red-100 text-red-800',
                                    'refunded' => 'bg-blue-100 text-blue-800',
                                ];
                            @endphp
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $sc[$payment->status] ?? 'bg-gray-100 text-gray-800' }}">{{ ucfirst($payment->status) }}</span>
                        </td>
                        <td class="px-6 py-4 text-gray-500 text-xs">{{ $payment->paid_at ? $payment->paid_at->format('M d, Y H:i') : '—' }}</td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.payments.show', $payment) }}" class="text-blue-600 hover:text-blue-800"><i class="fas fa-eye"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-6 py-12 text-center text-gray-400">No payments found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t">{{ $payments->links() }}</div>
</div>
@endsection
