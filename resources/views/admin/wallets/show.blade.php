@extends('admin.layouts.app')
@section('title', 'Wallet Details - LesGo Admin')
@section('header', 'Wallet Details')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">User Information</h3>
        <div class="space-y-3 text-sm">
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Name</span><span>{{ $wallet->user->name ?? 'N/A' }}</span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Email</span><span>{{ $wallet->user->email ?? 'N/A' }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Wallet ID</span><span>#{{ $wallet->id }}</span></div>
        </div>
    </div>

    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-semibold text-gray-800 mb-2">Current Balance</h3>
            <p class="text-3xl font-bold text-green-600">₱{{ number_format($wallet->balance, 2) }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ $wallet->currency }} &middot; Last updated {{ $wallet->updated_at->format('M d, Y H:i') }}</p>
            <div class="mt-4">
                <a href="{{ route('admin.wallets.transactions', $wallet) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium"><i class="fas fa-list mr-1"></i> View All Transactions</a>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b">
            <h3 class="font-semibold text-gray-800">Recent Transactions</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="text-left px-6 py-3 text-gray-500 font-medium">Type</th>
                        <th class="text-left px-6 py-3 text-gray-500 font-medium">Amount</th>
                        <th class="text-left px-6 py-3 text-gray-500 font-medium">Before</th>
                        <th class="text-left px-6 py-3 text-gray-500 font-medium">After</th>
                        <th class="text-left px-6 py-3 text-gray-500 font-medium">Description</th>
                        <th class="text-left px-6 py-3 text-gray-500 font-medium">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($recentTransactions as $tx)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3">
                                @php
                                    $tc = [
                                        'credit' => 'bg-green-100 text-green-800',
                                        'debit' => 'bg-red-100 text-red-800',
                                    ];
                                @endphp
                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $tc[$tx->type] ?? 'bg-gray-100 text-gray-800' }}">{{ ucfirst($tx->type) }}</span>
                            </td>
                            <td class="px-6 py-3 font-medium {{ $tx->type === 'credit' ? 'text-green-600' : 'text-red-600' }}">
                                {{ $tx->type === 'credit' ? '+' : '-' }}₱{{ number_format($tx->amount, 2) }}
                            </td>
                            <td class="px-6 py-3 text-gray-500">₱{{ number_format($tx->balance_before, 2) }}</td>
                            <td class="px-6 py-3 text-gray-500">₱{{ number_format($tx->balance_after, 2) }}</td>
                            <td class="px-6 py-3 text-gray-700 text-xs max-w-[200px] truncate">{{ $tx->description ?? '—' }}</td>
                            <td class="px-6 py-3 text-gray-500 text-xs">{{ $tx->created_at->format('M d, Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-8 text-center text-gray-400">No transactions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b">
            <h3 class="font-semibold text-gray-800">Recent Top-ups</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="text-left px-6 py-3 text-gray-500 font-medium">Amount</th>
                        <th class="text-left px-6 py-3 text-gray-500 font-medium">Fee</th>
                        <th class="text-left px-6 py-3 text-gray-500 font-medium">Status</th>
                        <th class="text-left px-6 py-3 text-gray-500 font-medium">Method</th>
                        <th class="text-left px-6 py-3 text-gray-500 font-medium">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($recentTopUps as $topUp)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 font-medium">₱{{ number_format($topUp->amount, 2) }}</td>
                            <td class="px-6 py-3 text-gray-500">₱{{ number_format($topUp->fee, 2) }}</td>
                            <td class="px-6 py-3">
                                @php
                                    $tsc = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'paid' => 'bg-green-100 text-green-800',
                                        'failed' => 'bg-red-100 text-red-800',
                                    ];
                                @endphp
                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $tsc[$topUp->status] ?? 'bg-gray-100 text-gray-800' }}">{{ ucfirst($topUp->status) }}</span>
                            </td>
                            <td class="px-6 py-3 text-gray-700">{{ ucfirst($topUp->payment_method ?? 'N/A') }}</td>
                            <td class="px-6 py-3 text-gray-500 text-xs">{{ $topUp->created_at->format('M d, Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400">No top-ups yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
