@extends('admin.layouts.app')
@section('title', 'Promos - LesGo Admin')
@section('header', 'Promos / Vouchers')

@section('actions')
<a href="{{ route('admin.vouchers.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-plus mr-1"></i> Add Promo</a>
@endsection

@section('content')
<x-filter-panel action="{{ request()->url() }}">
    <x-filter-input name="search" label="Search" placeholder="Code or title..." />
    <x-filter-input name="type" label="Type" type="select" :options="['' => 'All', 'percentage' => 'Percentage', 'fixed' => 'Fixed', 'free_delivery' => 'Free Delivery', 'buy_one_get_one' => 'BOGO']" />
    <x-filter-input name="is_active" label="Status" type="select" :options="['' => 'All', '1' => 'Active', '0' => 'Inactive']" />
</x-filter-panel>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="responsive-table w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Code</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Title</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Type</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Value</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Min Order</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Expires</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Active</th>
                    <th class="text-right px-6 py-3 text-gray-500 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($vouchers as $voucher)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-mono text-xs font-bold text-gray-800">{{ $voucher->code }}</td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-800">{{ $voucher->title }}</div>
                            <div class="text-xs text-gray-500 truncate max-w-xs">{{ $voucher->description }}</div>
                        </td>
                        <td class="px-6 py-4"><span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-700">{{ $voucher->type }}</span></td>
                        <td class="px-6 py-4">
                            @if($voucher->type === 'percentage') {{ $voucher->value }}% @if($voucher->max_discount) (max ₱{{ number_format($voucher->max_discount,0) }}) @endif
                            @elseif($voucher->type === 'fixed') ₱{{ number_format($voucher->value,2) }}
                            @else {{ $voucher->discount_text ?? $voucher->type }} @endif
                        </td>
                        <td class="px-6 py-4">₱{{ number_format($voucher->min_order_value,2) }}</td>
                        <td class="px-6 py-4 text-xs">{{ $voucher->expires_at ? $voucher->expires_at->format('Y-m-d') : 'No expiry' }}</td>
                        <td class="px-6 py-4"><x-status-badge :status="$voucher->is_active ? 'active' : 'inactive'" /></td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.vouchers.show', $voucher) }}" class="text-blue-600 hover:text-blue-800 mr-2"><i class="fas fa-eye"></i></a>
                            <a href="{{ route('admin.vouchers.edit', $voucher) }}" class="text-yellow-600 hover:text-yellow-800"><i class="fas fa-edit"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-empty-state icon="fa-tags" title="No promos found" description="Create a promo to start offering discounts." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t">{{ $vouchers->links() }}</div>
</div>
@endsection
