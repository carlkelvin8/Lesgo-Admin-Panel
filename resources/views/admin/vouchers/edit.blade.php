@extends('admin.layouts.app')
@section('title', 'Edit Promo - LesGo Admin')
@section('header', 'Edit Promo: ' . $voucher->code)

@section('content')
<div class="max-w-3xl">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <form method="POST" action="{{ route('admin.vouchers.update', $voucher) }}">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Code *</label>
                    <input type="text" name="code" value="{{ old('code', $voucher->code) }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 uppercase">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
                    <select name="type" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="percentage" @selected(old('type', $voucher->type)=='percentage')>Percentage</option>
                        <option value="fixed" @selected(old('type', $voucher->type)=='fixed')>Fixed Amount</option>
                        <option value="free_delivery" @selected(old('type', $voucher->type)=='free_delivery')>Free Delivery</option>
                        <option value="buy_one_get_one" @selected(old('type', $voucher->type)=='buy_one_get_one')>Buy One Get One</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                <input type="text" name="title" value="{{ old('title', $voucher->title) }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2">{{ old('description', $voucher->description) }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Discount Text</label>
                    <input type="text" name="discount_text" value="{{ old('discount_text', $voucher->discount_text) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Min Order Label</label>
                    <input type="text" name="min_order" value="{{ old('min_order', $voucher->min_order) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Value *</label>
                    <input type="number" step="0.01" name="value" value="{{ old('value', $voucher->value) }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Max Discount (₱)</label>
                    <input type="number" step="0.01" name="max_discount" value="{{ old('max_discount', $voucher->max_discount) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Min Order Value (₱) *</label>
                    <input type="number" step="0.01" name="min_order_value" value="{{ old('min_order_value', $voucher->min_order_value) }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Max Uses (total)</label>
                    <input type="number" name="max_uses" value="{{ old('max_uses', $voucher->max_uses) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Expires At</label>
                    <input type="date" name="expires_at" value="{{ old('expires_at', $voucher->expires_at ? $voucher->expires_at->format('Y-m-d') : '') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="border rounded-lg p-3">
                    <p class="text-sm font-medium text-gray-700 mb-2">User Restrictions</p>
                    <label class="flex items-center gap-2 mb-2">
                        <input type="checkbox" name="new_users_only" value="1" @checked(old('new_users_only', $voucher->user_restrictions['new_users_only'] ?? false)) class="rounded border-gray-300 text-blue-600">
                        <span class="text-sm text-gray-700">New users only</span>
                    </label>
                    <label class="block text-sm text-gray-700 mb-1">Max uses per user</label>
                    <input type="number" name="max_uses_per_user" value="{{ old('max_uses_per_user', $voucher->user_restrictions['max_uses_per_user'] ?? '') }}" placeholder="1" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div class="border rounded-lg p-3">
                    <p class="text-sm font-medium text-gray-700 mb-2">Applicable Services</p>
                    <p class="text-xs text-gray-500 mb-2">Leave empty for all services</p>
                    @foreach($services as $service)
                        <label class="flex items-center gap-2 mb-1">
                            <input type="checkbox" name="applicable_services[]" value="{{ $service->id }}" @checked(in_array($service->id, old('applicable_services', $voucher->applicable_services ?? []))) class="rounded border-gray-300 text-blue-600">
                            <span class="text-sm text-gray-700">{{ $service->name }} ({{ $service->code }})</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="mb-6">
                <label class="flex items-center gap-2">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $voucher->is_active)) class="rounded border-gray-300 text-blue-600">
                    <span class="text-sm text-gray-700">Active (visible to users)</span>
                </label>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg text-sm font-medium">Save Changes</button>
                <a href="{{ route('admin.vouchers.show', $voucher) }}" class="text-gray-500 hover:text-gray-700 text-sm">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
