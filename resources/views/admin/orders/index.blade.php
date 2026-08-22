@extends('admin.layouts.app')
@section('title', 'Orders - LesGo Admin')
@section('header', 'Orders Management')

@section('content')
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <x-filter-panel action="{{ request()->url() }}">
        <x-filter-input name="search" label="Search" placeholder="Search by ID, customer, partner..." value="{{ request('search') }}" />
        <x-filter-input name="status" label="Status" type="select" :options="['pending' => 'Pending', 'accepted' => 'Accepted', 'driver_arrived' => 'Driver Arrived', 'in_progress' => 'In Progress', 'picked_up' => 'Picked Up', 'completed' => 'Completed', 'cancelled' => 'Cancelled']" />
        <x-filter-input name="payment_status" label="Payment" type="select" :options="['pending' => 'Pending', 'paid' => 'Paid', 'failed' => 'Failed', 'refunded' => 'Refunded']" />
        <x-filter-input name="date_from" label="From" type="date" />
        <x-filter-input name="date_to" label="To" type="date" />
    </x-filter-panel>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="responsive-table w-full text-sm">
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
                            <x-status-badge status="{{ $order->status }}" />
                        </td>
                        <td class="px-6 py-4">
                            <x-status-badge status="{{ $order->payment_status }}" />
                        </td>
                        <td class="px-6 py-4">₱{{ number_format($order->actual_fare ?? $order->estimated_fare, 2) }}</td>
                        <td class="px-6 py-4 text-gray-500 text-xs">{{ $order->created_at->format('M d, Y H:i') }}</td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.orders.show', $order) }}" class="text-blue-600 hover:text-blue-800"><i class="fas fa-eye"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-empty-state icon="fa-shopping-cart" title="No orders found" description="There are no orders matching your criteria." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t">{{ $orders->links() }}</div>
</div>
@endsection
