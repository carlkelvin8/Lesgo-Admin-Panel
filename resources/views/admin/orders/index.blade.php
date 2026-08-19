@extends('admin.layouts.app')
@section('title', 'Orders - LesGo Admin')
@section('header', 'Orders Management')

@section('content')
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[200px]">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by ID, customer, partner..."
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-blue-500 outline-none">
        </div>
        <div>
            <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">All Status</option>
                @foreach(['pending','accepted','driver_arrived','in_progress','picked_up','completed','cancelled'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <select name="payment_status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">All Payment</option>
                @foreach(['pending','paid','failed','refunded'] as $ps)
                    <option value="{{ $ps }}" {{ request('payment_status') === $ps ? 'selected' : '' }}>{{ ucfirst($ps) }}</option>
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
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Order #</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Customer</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Partner</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Status</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Payment</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Fare</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Date</th>
                    <th class="text-right px-6 py-3 text-gray-500 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($orders as $order)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-blue-600">#{{ $order->id }}</td>
                        <td class="px-6 py-4 text-gray-700">{{ $order->customer->name ?? 'N/A' }}</td>
                        <td class="px-6 py-4 text-gray-700">{{ $order->partner->name ?? 'N/A' }}</td>
                        <td class="px-6 py-4">
                            @php
                                $sc = ['pending'=>'bg-yellow-100 text-yellow-800','accepted'=>'bg-blue-100 text-blue-800','picked_up'=>'bg-indigo-100 text-indigo-800','completed'=>'bg-green-100 text-green-800','cancelled'=>'bg-red-100 text-red-800','in_progress'=>'bg-purple-100 text-purple-800','driver_arrived'=>'bg-cyan-100 text-cyan-800'];
                            @endphp
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $sc[$order->status] ?? 'bg-gray-100 text-gray-800' }}">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @php $pc = ['pending'=>'text-yellow-600','paid'=>'text-green-600','failed'=>'text-red-600','refunded'=>'text-orange-600']; @endphp
                            <span class="text-xs font-medium {{ $pc[$order->payment_status] ?? 'text-gray-600' }}">{{ ucfirst($order->payment_status) }}</span>
                        </td>
                        <td class="px-6 py-4">₱{{ number_format($order->actual_fare ?? $order->estimated_fare, 2) }}</td>
                        <td class="px-6 py-4 text-gray-500 text-xs">{{ $order->created_at->format('M d, Y H:i') }}</td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.orders.show', $order) }}" class="text-blue-600 hover:text-blue-800"><i class="fas fa-eye"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-6 py-12 text-center text-gray-400">No orders found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t">{{ $orders->links() }}</div>
</div>
@endsection
