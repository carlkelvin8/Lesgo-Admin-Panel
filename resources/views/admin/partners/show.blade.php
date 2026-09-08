@extends('admin.layouts.app')
@section('title', 'Partner Details - LesGo Admin')
@section('header', 'Partner Details')

@section('actions')
<a href="{{ route('admin.partners.menu.index', $partner) }}" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-utensils mr-1"></i> Menu</a>
<a href="{{ route('admin.partners.staff.index', $partner) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-user-group mr-1"></i> Staff</a>
<a href="{{ route('admin.partners.edit', $partner) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-edit mr-1"></i> Edit</a>
<button type="button" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm"
    x-data
    @click="$dispatch('confirm-modal', {
        title: 'Delete Partner',
        message: 'Permanently delete {{ addslashes($partner->name) }}, its owner account, assigned riders, orders, payments, menu, staff, analytics, and all other linked data? This cannot be undone.',
        confirmText: 'Delete Restaurant & Data',
        onConfirm: () => {
            const form = document.createElement('form'); form.method = 'POST'; form.action = '{{ route('admin.partners.destroy', $partner) }}';
            const token = document.createElement('input'); token.type = 'hidden'; token.name = '_token'; token.value = '{{ csrf_token() }}'; form.appendChild(token);
            const method = document.createElement('input'); method.type = 'hidden'; method.name = '_method'; method.value = 'DELETE'; form.appendChild(method);
            document.body.appendChild(form); form.submit();
        }
    })"><i class="fas fa-trash mr-1"></i> Delete</button>
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
        @php $registrationFee = \App\Models\RegistrationFeePayment::where('user_id', $partner->user_id)->where('account_type', 'merchant')->first(); @endphp
        @if($registrationFee)
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 {{ $registrationFee->is_active ? 'border-green-500' : 'border-yellow-500' }}">
            <h3 class="font-semibold text-gray-800 mb-4">Registration Fee & Application — Merchant</h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div><p class="text-gray-500">Account Status</p><p class="font-bold {{ $registrationFee->is_active ? 'text-green-600' : 'text-red-600' }}">{{ $registrationFee->is_active ? 'Active' : 'Restricted' }} <span class="text-xs font-normal text-gray-500">({{ $registrationFee->application_status }}/{{ $registrationFee->payment_status }})</span></p><p class="text-xs text-gray-400 mt-1">{{ $registrationFee->payment_status === 'paid' && $registrationFee->application_status === 'pending' ? 'Payment Successful – Application Pending Approval' : ($registrationFee->application_status === 'approved' && $registrationFee->payment_status !== 'paid' ? 'Application Approved – Registration Fee Payment Required' : '') }}</p></div>
                <div><p class="text-gray-500">Amount</p><p class="font-semibold">₱{{ number_format($registrationFee->amount,2) }} {{ $registrationFee->currency }}</p></div>
                <div><p class="text-gray-500">Payment Status</p><span class="px-2 py-1 text-xs rounded-full {{ $registrationFee->payment_status==='paid' ? 'bg-green-100 text-green-700' : ($registrationFee->payment_status==='pending' ? 'bg-blue-100 text-blue-700' : ($registrationFee->payment_status==='failed' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700')) }}">{{ ucfirst($registrationFee->payment_status) }}</span></div>
                <div><p class="text-gray-500">Application</p><span class="px-2 py-1 text-xs rounded-full {{ $registrationFee->application_status==='approved' ? 'bg-green-100 text-green-700' : ($registrationFee->application_status==='rejected' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">{{ ucfirst($registrationFee->application_status) }}</span></div>
                <div class="col-span-2"><p class="text-gray-500">PayMongo Reference</p><p class="font-mono text-xs break-all">{{ $registrationFee->paymongo_reference ?? '—' }}</p>@if($registrationFee->checkout_url)<a href="{{ $registrationFee->checkout_url }}" target="_blank" class="text-xs text-blue-600 hover:underline">View Checkout URL</a>@endif</div>
                <div><p class="text-gray-500">Payment Date</p><p class="text-sm">{{ $registrationFee->payment_date?->format('M d, Y H:i') ?? '—' }}</p></div>
                <div><p class="text-gray-500">Activated At</p><p class="text-sm">{{ $registrationFee->activated_at?->format('M d, Y H:i') ?? '—' }}</p></div>
            </div>
            <div class="mt-4 flex gap-2">
                @if($registrationFee->application_status !== 'approved')
                <form method="POST" action="{{ route('admin.registration-fees.approve', $registrationFee) }}">@csrf<button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm" onclick="return confirm('Approve? Will only activate if fee PAID. Approval does not bypass fee.')">Approve Application</button></form>
                @endif
                @if($registrationFee->application_status !== 'rejected')
                <form method="POST" action="{{ route('admin.registration-fees.reject', $registrationFee) }}">@csrf<button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm">Reject Application</button></form>
                @endif
            </div>
        </div>
        @endif
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
