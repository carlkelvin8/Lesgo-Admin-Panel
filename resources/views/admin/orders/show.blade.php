@extends('admin.layouts.app')
@section('title', 'Order Details - LesGo Admin')
@section('header', 'Order #' . $order->id)

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Order Info -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Order Information</h3>
        <div class="space-y-3 text-sm">
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Status</span><x-status-badge status="{{ $order->status }}" /></div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Customer</span><span>{{ $order->customer?->name ?? 'N/A' }}</span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Partner</span><span>{{ $order->partner?->name ?? 'N/A' }}</span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Driver</span><span>{{ $order->driver?->user?->name ?? 'Unassigned' }}</span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Service</span><span>{{ $order->service?->name ?? 'N/A' }}</span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Payment</span><span>{{ ucfirst($order->payment_status) }} ({{ ucfirst($order->payment_method ?? 'N/A') }})</span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Created</span><span>{{ $order->created_at->format('M d, Y H:i') }}</span></div>
        </div>

        <!-- Addresses -->
        <div class="mt-4 pt-4 border-t">
            <p class="text-xs text-gray-500 mb-2 font-medium">Pickup</p>
            <p class="text-sm text-gray-700 mb-2">{{ $order->pickup_address ?? 'N/A' }}</p>
            <p class="text-xs text-gray-500 mb-2 font-medium">Dropoff</p>
            <p class="text-sm text-gray-700">{{ $order->dropoff_address ?? 'N/A' }}</p>
        </div>
    </div>

    <!-- Fare Breakdown + Status Update -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Fare Breakdown</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-gray-50 rounded-lg p-3 text-center">
                    <p class="text-xs text-gray-500">Estimated</p>
                    <p class="text-lg font-bold text-gray-800">₱{{ number_format($order->estimated_fare, 2) }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3 text-center">
                    <p class="text-xs text-gray-500">Actual</p>
                    <p class="text-lg font-bold text-green-600">₱{{ number_format($order->actual_fare ?? 0, 2) }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3 text-center">
                    <p class="text-xs text-gray-500">Platform Fee</p>
                    <p class="text-lg font-bold text-blue-600">₱{{ number_format($order->platform_fee, 2) }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3 text-center">
                    <p class="text-xs text-gray-500">Driver Share</p>
                    <p class="text-lg font-bold text-purple-600">₱{{ number_format($order->driver_share, 2) }}</p>
                </div>
            </div>
        </div>

        <!-- Update Status -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Update Status</h3>
            <form method="POST" action="{{ route('admin.orders.status', $order) }}">
                @csrf
                @method('PATCH')
                <div class="flex flex-wrap gap-3 items-end">
                    <div class="flex-1 min-w-[200px]">
                        <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            @foreach(['pending','accepted','driver_arrived','in_progress','picked_up','completed','cancelled'] as $s)
                                <option value="{{ $s }}" {{ $order->status === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <input type="text" name="cancel_reason" placeholder="Cancel reason (if cancelling)" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg text-sm font-medium">Update</button>
                </div>
            </form>
        </div>

        @if($order->lesbuyItems->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b"><h3 class="font-semibold text-gray-800">Order Items</h3></div>
            <table class="responsive-table w-full text-sm">
                <thead class="bg-gray-50"><tr><th class="text-left px-6 py-3">Item</th><th class="text-left px-6 py-3">Qty</th><th class="text-left px-6 py-3">Price</th><th class="text-left px-6 py-3">Status</th></tr></thead>
                <tbody class="divide-y">
                    @foreach($order->lesbuyItems as $item)
                    <tr><td class="px-6 py-3"><p class="font-medium">{{ $item->name }}</p>@if($item->notes)<p class="text-xs text-gray-500">{{ $item->notes }}</p>@endif</td><td class="px-6 py-3">{{ $item->quantity }} {{ $item->unit }}</td><td class="px-6 py-3">₱{{ number_format($item->actual_price ?? $item->estimated_price ?? 0, 2) }}</td><td class="px-6 py-3">{{ ucfirst($item->status) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- Tracking events -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Tracking Timeline</h3>
            <div class="space-y-4 text-sm">
                @forelse($order->trackingEvents as $event)
                    <div class="flex gap-3 border-l-2 {{ $event->is_milestone ? 'border-blue-500' : 'border-gray-200' }} pl-4 py-1">
                        <span class="{{ $event->is_milestone ? 'text-blue-600' : 'text-gray-400' }}"><i class="fas fa-circle text-[8px]"></i></span>
                        <div class="flex-1">
                            <p class="font-medium">{{ $event->event_title }}</p>
                            @if($event->event_description)<p class="text-xs text-gray-500">{{ $event->event_description }}</p>@endif
                            @if($event->location_address)<p class="text-xs text-gray-500"><i class="fas fa-location-dot mr-1"></i>{{ $event->location_address }}</p>@endif
                        </div>
                        <div class="text-right text-xs text-gray-500"><p>{{ $event->event_time?->format('M d, Y H:i') }}</p><p>{{ $event->user->name ?? 'System' }}</p></div>
                    </div>
                @empty
                    <p class="text-gray-400">No tracking events yet. Future admin status changes will appear here.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
