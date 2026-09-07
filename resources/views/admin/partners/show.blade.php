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
            @php $logo = $partner->logo_url; if ($logo && !\Illuminate\Support\Str::startsWith($logo, ['http://','https://'])) { try { $logo = \Illuminate\Support\Facades\Storage::disk(config('filesystems.default') === 's3' ? 's3' : 'public')->url($logo); } catch (\Throwable $e) {} } @endphp
            @if($logo)
                <img src="{{ $logo }}" class="w-16 h-16 rounded-full mx-auto mb-3 object-cover border">
            @else
                <div class="w-16 h-16 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center font-bold text-xl mx-auto mb-3">
                    {{ substr($partner->name, 0, 1) }}
                </div>
            @endif
            <h3 class="text-xl font-bold text-gray-800">{{ $partner->name }}</h3>
            @if($partner->legal_name)<p class="text-gray-500 text-sm">{{ $partner->legal_name }}</p>@endif
        </div>
        @if($partner->cover_image_url)
            @php $cover = $partner->cover_image_url; if (!\Illuminate\Support\Str::startsWith($cover, ['http://','https://'])) { try { $cover = \Illuminate\Support\Facades\Storage::disk(config('filesystems.default') === 's3' ? 's3' : 'public')->url($cover); } catch (\Throwable $e) {} } @endphp
            <div class="mb-4"><p class="text-xs text-gray-500 mb-1">Cover Image</p><img src="{{ $cover }}" alt="Cover" class="w-full h-32 object-cover rounded-lg border"></div>
        @endif
        <div class="space-y-3 text-sm">
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Category</span><span>{{ $partner->category ?? '-' }}</span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Status</span>
                <x-status-badge :status="$partner->status" />
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
        @if(!empty($partner->documents))
            <div class="mt-4 pt-4 border-t">
                <p class="text-xs text-gray-500 mb-2">Documents</p>
                <div class="grid grid-cols-2 gap-3">
                    @foreach($partner->documents as $key => $url)
                        @if(is_string($url))
                            @php $docUrl = \Illuminate\Support\Str::startsWith($url, ['http://','https://']) ? $url : \Illuminate\Support\Facades\Storage::disk(config('filesystems.default') === 's3' ? 's3' : 'public')->url($url); @endphp
                            <a href="{{ $docUrl }}" target="_blank" class="block border rounded-lg p-2 hover:bg-gray-50"><p class="text-xs font-medium text-gray-500 uppercase">{{ str_replace('_',' ', $key) }}</p><img src="{{ $docUrl }}" alt="{{ $key }}" class="h-24 w-full object-cover rounded mt-1" onerror="this.style.display='none'"><p class="text-xs text-blue-600 truncate mt-1">View →</p></a>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif
        @if($partner->tax_id || $partner->support_email || $partner->support_phone)
            <div class="mt-4 pt-4 border-t space-y-2 text-sm">
                @if($partner->tax_id)<div class="flex justify-between"><span class="text-gray-500">Tax ID</span><span>{{ $partner->tax_id }}</span></div>@endif
                @if($partner->support_email)<div class="flex justify-between"><span class="text-gray-500">Support Email</span><span class="text-xs">{{ $partner->support_email }}</span></div>@endif
                @if($partner->support_phone)<div class="flex justify-between"><span class="text-gray-500">Support Phone</span><span>{{ $partner->support_phone }}</span></div>@endif
            </div>
        @endif
    </div>

    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b"><h3 class="font-semibold text-gray-800">Services</h3></div>
            <div class="overflow-x-auto">
                <table class="responsive-table w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr><th class="text-left px-6 py-3 text-gray-500 font-medium">Name</th><th class="text-left px-6 py-3 text-gray-500 font-medium">Code</th><th class="text-left px-6 py-3 text-gray-500 font-medium">Base Fare</th><th class="text-left px-6 py-3 text-gray-500 font-medium">Active</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($partner->services as $service)
                            <tr><td class="px-6 py-3">{{ $service->name }}</td><td class="px-6 py-3 text-gray-500">{{ $service->code }}</td><td class="px-6 py-3">₱{{ number_format($service->base_fare, 2) }}</td><td class="px-6 py-3">{{ $service->is_active ? 'Yes' : 'No' }}</td></tr>
                        @empty
                            <tr><td colspan="4"><x-empty-state icon="fa-inbox" title="No services" description="This partner has no services yet." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b"><h3 class="font-semibold text-gray-800">Recent Orders</h3></div>
            <div class="overflow-x-auto">
                <table class="responsive-table w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr><th class="text-left px-6 py-3 text-gray-500 font-medium">ID</th><th class="text-left px-6 py-3 text-gray-500 font-medium">Status</th><th class="text-left px-6 py-3 text-gray-500 font-medium">Fare</th><th class="text-left px-6 py-3 text-gray-500 font-medium">Date</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($partner->orders->take(10) as $order)
                            <tr><td class="px-6 py-3"><a href="{{ route('admin.orders.show', $order) }}" class="text-blue-600 hover:underline">#{{ $order->id }}</a></td><td class="px-6 py-3"><x-status-badge :status="$order->status" /></td><td class="px-6 py-3">₱{{ number_format($order->actual_fare ?? $order->estimated_fare, 2) }}</td><td class="px-6 py-3 text-gray-500 text-xs">{{ $order->created_at->diffForHumans() }}</td></tr>
                        @empty
                            <tr><td colspan="4"><x-empty-state icon="fa-inbox" title="No orders" description="This partner has no orders yet." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
