@extends('admin.layouts.app')
@section('title', 'Promo Details - LesGo Admin')
@section('header', 'Promo: ' . $voucher->code)

@section('actions')
<a href="{{ route('admin.vouchers.edit', $voucher) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-edit mr-1"></i> Edit</a>
<form method="POST" action="{{ route('admin.vouchers.toggle', $voucher) }}" class="inline" onsubmit="return confirm('Are you sure you want to {{ $voucher->is_active ? 'deactivate' : 'activate' }} this promo?')">
    @csrf
    <button type="submit" class="bg-{{ $voucher->is_active ? 'red' : 'green' }}-500 text-white px-4 py-2 rounded-lg text-sm">
        {{ $voucher->is_active ? 'Deactivate' : 'Activate' }}
    </button>
</form>
@endsection

@section('content')
<div class="max-w-3xl">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="space-y-4 text-sm">
            <div class="flex justify-between border-b pb-3"><span class="text-gray-500">Code</span><span class="font-mono font-bold text-gray-800">{{ $voucher->code }}</span></div>
            <div class="flex justify-between border-b pb-3"><span class="text-gray-500">Title</span><span class="font-medium text-gray-800">{{ $voucher->title }}</span></div>
            <div class="flex justify-between border-b pb-3"><span class="text-gray-500">Type</span><span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-700">{{ $voucher->type }}</span></div>
            <div class="flex justify-between border-b pb-3"><span class="text-gray-500">Value</span><span class="font-medium text-gray-800">
                @if($voucher->type === 'percentage') {{ $voucher->value }}% @if($voucher->max_discount) (max ₱{{ number_format($voucher->max_discount,0) }}) @endif
                @elseif($voucher->type === 'fixed') ₱{{ number_format($voucher->value,2) }}
                @else {{ $voucher->discount_text ?? $voucher->type }} @endif
            </span></div>
            <div class="flex justify-between border-b pb-3"><span class="text-gray-500">Min Order Value</span><span class="font-medium">₱{{ number_format($voucher->min_order_value,2) }}</span></div>
            <div class="flex justify-between border-b pb-3"><span class="text-gray-500">Max Uses</span><span class="font-medium">{{ $voucher->max_uses ?? 'Unlimited' }}</span></div>
            <div class="flex justify-between border-b pb-3"><span class="text-gray-500">Expires At</span><span class="font-medium">{{ $voucher->expires_at ? $voucher->expires_at->format('Y-m-d') : 'No expiry' }}</span></div>
            <div class="flex justify-between border-b pb-3"><span class="text-gray-500">Discount Text</span><span class="font-medium">{{ $voucher->discount_text ?? '-' }}</span></div>
            <div class="flex justify-between border-b pb-3"><span class="text-gray-500">Min Order Label</span><span class="font-medium">{{ $voucher->min_order ?? '-' }}</span></div>
            <div class="flex justify-between border-b pb-3"><span class="text-gray-500">Restrictions</span><span class="font-medium text-right text-xs">
                @if(!empty($voucher->user_restrictions['new_users_only'])) New users only<br>@endif
                @if(!empty($voucher->user_restrictions['max_uses_per_user'])) Max {{ $voucher->user_restrictions['max_uses_per_user'] }} per user<br>@endif
                @if(empty($voucher->user_restrictions)) None @endif
            </span></div>
            <div class="flex justify-between border-b pb-3"><span class="text-gray-500">Applicable Services</span><span class="font-medium">
                @if($voucher->applicable_services) {{ implode(', ', $voucher->applicable_services) }} @else All services @endif
            </span></div>
            <div class="flex justify-between"><span class="text-gray-500">Active</span>
                @if($voucher->is_active)<span class="text-green-600 font-medium">Yes</span>@else<span class="text-red-600 font-medium">No</span>@endif
            </div>
        </div>
        @if($voucher->description)
            <div class="mt-4 pt-4 border-t">
                <p class="text-xs text-gray-500 mb-1">Description</p>
                <p class="text-sm text-gray-700">{{ $voucher->description }}</p>
            </div>
        @endif

        <div class="mt-6 flex gap-3">
            <form method="POST" action="{{ route('admin.vouchers.destroy', $voucher) }}" onsubmit="return confirm('Delete this promo?')">
                @csrf @method('DELETE')
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm">Delete</button>
            </form>
        </div>
    </div>
</div>
@endsection
