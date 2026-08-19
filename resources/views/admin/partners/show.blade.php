@extends('admin.layouts.app')
@section('title', 'Partner Details - LesGo Admin')
@section('header', 'Partner Details')

@section('actions')
<a href="{{ route('admin.partners.menu.index', $partner) }}" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-utensils mr-1"></i> Menu</a>
<a href="{{ route('admin.partners.staff.index', $partner) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-user-group mr-1"></i> Staff</a>
<a href="{{ route('admin.partners.edit', $partner) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-edit mr-1"></i> Edit</a>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="text-center mb-4">
            @if($partner->logo_url)
                <img src="{{ $partner->logo_url }}" class="w-16 h-16 rounded-full mx-auto mb-3 object-cover">
            @else
                <div class="w-16 h-16 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center font-bold text-xl mx-auto mb-3">
                    {{ substr($partner->name, 0, 1) }}
                </div>
            @endif
            <h3 class="text-xl font-bold text-gray-800">{{ $partner->name }}</h3>
            @if($partner->legal_name)<p class="text-gray-500 text-sm">{{ $partner->legal_name }}</p>@endif
        </div>
        <div class="space-y-3 text-sm">
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Category</span><span>{{ $partner->category ?? '-' }}</span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Status</span>
                @php $sc = ['pending'=>'bg-yellow-100 text-yellow-800','approved'=>'bg-green-100 text-green-800','rejected'=>'bg-red-100 text-red-800']; @endphp
                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $sc[$partner->status] ?? 'bg-gray-100' }}">{{ ucfirst($partner->status) }}</span>
            </div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Rating</span><span>{{ $partner->rating }} <i class="fas fa-star text-yellow-400 text-xs"></i> ({{ $partner->total_reviews }} reviews)</span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Delivery Fee</span><span>₱{{ number_format($partner->delivery_fee, 2) }}</span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Min Order</span><span>₱{{ number_format($partner->min_order_amount, 2) }}</span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Est. Delivery</span><span>{{ $partner->estimated_delivery_minutes }} mins</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Open / Featured</span><span>{{ $partner->is_open ? '🟢' : '🔴' }} / {{ $partner->is_featured ? '⭐' : '-' }}</span></div>
        </div>
        @if($partner->description)
            <div class="mt-4 pt-4 border-t">
                <p class="text-xs text-gray-500 mb-1">Description</p>
                <p class="text-sm text-gray-700">{{ $partner->description }}</p>
            </div>
        @endif
    </div>

    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b"><h3 class="font-semibold text-gray-800">Services</h3></div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr><th class="text-left px-6 py-3 text-gray-500 font-medium">Name</th><th class="text-left px-6 py-3 text-gray-500 font-medium">Code</th><th class="text-left px-6 py-3 text-gray-500 font-medium">Base Fare</th><th class="text-left px-6 py-3 text-gray-500 font-medium">Active</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($partner->services as $service)
                            <tr><td class="px-6 py-3">{{ $service->name }}</td><td class="px-6 py-3 text-gray-500">{{ $service->code }}</td><td class="px-6 py-3">₱{{ number_format($service->base_fare, 2) }}</td><td class="px-6 py-3">{{ $service->is_active ? 'Yes' : 'No' }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-6 text-center text-gray-400">No services.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b"><h3 class="font-semibold text-gray-800">Recent Orders</h3></div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr><th class="text-left px-6 py-3 text-gray-500 font-medium">ID</th><th class="text-left px-6 py-3 text-gray-500 font-medium">Status</th><th class="text-left px-6 py-3 text-gray-500 font-medium">Fare</th><th class="text-left px-6 py-3 text-gray-500 font-medium">Date</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($partner->orders->take(10) as $order)
                            <tr><td class="px-6 py-3"><a href="{{ route('admin.orders.show', $order) }}" class="text-blue-600 hover:underline">#{{ $order->id }}</a></td><td class="px-6 py-3"><span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100">{{ ucfirst($order->status) }}</span></td><td class="px-6 py-3">₱{{ number_format($order->actual_fare ?? $order->estimated_fare, 2) }}</td><td class="px-6 py-3 text-gray-500 text-xs">{{ $order->created_at->diffForHumans() }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-6 text-center text-gray-400">No orders.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
