@extends('admin.layouts.app')
@section('title', 'Registration Fees - LesGo Admin')
@section('header', 'Registration Fees (Rider & Merchant — PayMongo)')

@section('content')
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <div class="flex items-start justify-between gap-4 mb-4">
        <div>
            <h3 class="font-semibold text-gray-800">Rider Package Pricing</h3>
            <p class="text-xs text-gray-500 mt-1">These prices control new rider registration and package upgrade charges.</p>
        </div>
        <span class="px-2 py-1 text-xs rounded-full bg-blue-50 text-blue-700">PHP · One-time</span>
    </div>
    <form method="POST" action="{{ route('admin.registration-fees.rider-prices.update') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        @csrf
        @method('PUT')
        <label class="block">
            <span class="text-sm text-gray-600">Basic</span>
            <div class="mt-1 flex rounded-lg border border-gray-300 overflow-hidden"><span class="bg-gray-50 px-3 py-2 text-gray-500">₱</span><input type="number" name="basic" value="{{ old('basic', $riderPackagePrices['basic']) }}" min="0" max="1000000" step="0.01" required class="w-full border-0 px-3 py-2 focus:ring-2 focus:ring-purple-500"></div>
        </label>
        <label class="block">
            <span class="text-sm text-gray-600">Advance</span>
            <div class="mt-1 flex rounded-lg border border-gray-300 overflow-hidden"><span class="bg-gray-50 px-3 py-2 text-gray-500">₱</span><input type="number" name="advance" value="{{ old('advance', $riderPackagePrices['advance']) }}" min="0" max="1000000" step="0.01" required class="w-full border-0 px-3 py-2 focus:ring-2 focus:ring-purple-500"></div>
        </label>
        <label class="block">
            <span class="text-sm text-gray-600">Elite</span>
            <div class="mt-1 flex rounded-lg border border-gray-300 overflow-hidden"><span class="bg-gray-50 px-3 py-2 text-gray-500">₱</span><input type="number" name="elite" value="{{ old('elite', $riderPackagePrices['pro']) }}" min="0" max="1000000" step="0.01" required class="w-full border-0 px-3 py-2 focus:ring-2 focus:ring-purple-500"></div>
        </label>
        <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-5 py-2 rounded-lg font-medium">Save Rider Prices</button>
    </form>
    @error('basic')<p class="text-xs text-red-600 mt-2">{{ $message }}</p>@enderror
    @error('advance')<p class="text-xs text-red-600 mt-2">{{ $message }}</p>@enderror
    @error('elite')<p class="text-xs text-red-600 mt-2">{{ $message }}</p>@enderror
</div>

<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <div class="grid grid-cols-2 md:grid-cols-8 gap-4 mb-4 text-center">
        <div class="bg-gray-50 p-3 rounded-lg"><div class="text-xs text-gray-500">Total</div><div class="text-xl font-bold">{{ $stats['total'] }}</div></div>
        <div class="bg-blue-50 p-3 rounded-lg"><div class="text-xs text-gray-500">Rider</div><div class="text-xl font-bold text-blue-600">{{ $stats['rider'] }}</div></div>
        <div class="bg-orange-50 p-3 rounded-lg"><div class="text-xs text-gray-500">Merchant</div><div class="text-xl font-bold text-orange-600">{{ $stats['merchant'] }}</div></div>
        <div class="bg-green-50 p-3 rounded-lg"><div class="text-xs text-gray-500">Paid</div><div class="text-xl font-bold text-green-600">{{ $stats['paid'] }}</div></div>
        <div class="bg-purple-50 p-3 rounded-lg"><div class="text-xs text-gray-500">Waived</div><div class="text-xl font-bold text-purple-600">{{ $stats['waived'] }}</div></div>
        <div class="bg-yellow-50 p-3 rounded-lg"><div class="text-xs text-gray-500">Approved</div><div class="text-xl font-bold text-yellow-600">{{ $stats['approved'] }}</div></div>
        <div class="bg-teal-50 p-3 rounded-lg"><div class="text-xs text-gray-500">Active</div><div class="text-xl font-bold text-teal-600">{{ $stats['active'] }}</div></div>
        <div class="bg-red-50 p-3 rounded-lg"><div class="text-xs text-gray-500">Restricted</div><div class="text-xl font-bold text-red-600">{{ $stats['restricted'] }}</div></div>
    </div>
    <x-filter-panel action="{{ request()->url() }}">
        <x-filter-input name="search" label="Search" placeholder="PayMongo ref or user..." />
        <x-filter-input name="account_type" label="Type" type="select" :options="['' => 'All', 'rider' => 'Rider', 'merchant' => 'Merchant']" />
        <x-filter-input name="payment_status" label="Payment" type="select" :options="['' => 'All', 'unpaid' => 'Unpaid', 'pending' => 'Pending', 'paid' => 'Paid', 'waived' => 'Waived', 'failed' => 'Failed', 'expired' => 'Expired']" />
        <x-filter-input name="application_status" label="Application" type="select" :options="['' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected']" />
        <x-filter-input name="is_active" label="Account" type="select" :options="['' => 'All', '1' => 'Active', '0' => 'Restricted']" />
    </x-filter-panel>
    <p class="text-xs text-gray-400 mt-2">Active = Approved + (Paid via PayMongo or explicitly Waived by Admin).</p>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="responsive-table w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">User</th>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">Type</th>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">Amount</th>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">Payment</th>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">Application</th>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">PayMongo Ref</th>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">Account</th>
                    <th class="text-right px-4 py-3 text-gray-500 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($fees as $f)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-800">{{ $f->user?->name ?? 'User #'.$f->user_id }}</div>
                            <div class="text-xs text-gray-500">{{ $f->user?->email }}</div>
                        </td>
                        <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full {{ $f->account_type==='rider' ? 'bg-blue-100 text-blue-700' : 'bg-orange-100 text-orange-700' }}">{{ ucfirst($f->account_type) }}</span></td>
                        <td class="px-4 py-3 font-semibold">₱{{ number_format($f->amount,2) }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs rounded-full {{ $f->payment_status==='paid' ? 'bg-green-100 text-green-700' : ($f->payment_status==='waived' ? 'bg-purple-100 text-purple-700' : ($f->payment_status==='pending' ? 'bg-blue-100 text-blue-700' : ($f->payment_status==='failed' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700'))) }}">{{ ucfirst($f->payment_status) }}</span>
                            @if($f->payment_date)<div class="text-xs text-gray-400">{{ $f->payment_date->format('M d, Y') }}</div>@endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs rounded-full {{ $f->application_status==='approved' ? 'bg-green-100 text-green-700' : ($f->application_status==='rejected' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">{{ ucfirst($f->application_status) }}</span>
                            @if($f->approved_at)<div class="text-xs text-gray-400">by {{ $f->approver?->name ?? $f->approved_by }}</div>@endif
                        </td>
                        <td class="px-4 py-3 font-mono text-xs"><div class="truncate max-w-[160px]">{{ $f->paymongo_reference }}</div><div class="text-gray-400 truncate max-w-[160px]">{{ $f->paymongo_checkout_id }}</div></td>
                        <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full {{ $f->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $f->is_active ? 'Active' : 'Restricted' }}</span>@if($f->is_grandfathered)<span class="text-xs text-gray-400 block">Grandfathered</span>@endif</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.registration-fees.show', $f) }}" class="text-blue-600 hover:text-blue-800 mr-2"><i class="fas fa-eye"></i></a>
                            @if($f->isFeeSatisfied() && $f->application_status !== 'approved')
                                <form action="{{ route('admin.registration-fees.approve', $f) }}" method="POST" class="inline">@csrf<button type="submit" class="text-green-600 hover:text-green-800" title="Approve (will activate if paid)"><i class="fas fa-check"></i></button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-empty-state icon="fa-money-bill" title="No registration fees" description="Rider & merchant fees will appear here." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t">{{ $fees->links() }}</div>
</div>
@endsection
