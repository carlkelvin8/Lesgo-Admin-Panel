@extends('admin.layouts.app')
@section('title', 'Wallet Details - LesGo Admin')
@section('header', 'Wallet Details')

@section('actions')
<a href="{{ route('admin.wallets.top-ups.index', ['search' => $wallet->user->email ?? '']) }}" class="rounded-lg border bg-white px-4 py-2 text-sm text-gray-700"><i class="fas fa-arrow-up-right-dots mr-1"></i> Review Top-ups</a>
@endsection

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

@if(auth()->user()->hasAdminPermission('wallets.manage'))
<div class="mt-6 rounded-xl border border-purple-100 bg-white p-6 shadow-sm">
    <h3 class="font-semibold text-gray-900">Manual wallet adjustment</h3>
    <p class="mt-1 text-sm text-gray-500">Every credit or debit creates an immutable ledger record with your administrator ID.</p>
    <form method="POST" action="{{ route('admin.wallets.adjust', $wallet) }}" class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-5" onsubmit="return confirm('Record this wallet adjustment?')">
        @csrf
        <div><label class="mb-1 block text-xs font-medium">Type</label><select name="type" required class="w-full rounded-lg border px-3 py-2"><option value="credit">Credit</option><option value="debit">Debit</option></select></div>
        <div><label class="mb-1 block text-xs font-medium">Amount</label><input type="number" name="amount" min="0.01" step="0.01" required class="w-full rounded-lg border px-3 py-2"></div>
        <div><label class="mb-1 block text-xs font-medium">Reference</label><input name="reference" maxlength="255" class="w-full rounded-lg border px-3 py-2" placeholder="Optional ticket/reference"></div>
        <div class="lg:col-span-2"><label class="mb-1 block text-xs font-medium">Reason</label><div class="flex gap-2"><input name="reason" minlength="10" maxlength="1000" required class="min-w-0 flex-1 rounded-lg border px-3 py-2" placeholder="Required operational reason"><button class="rounded-lg bg-purple-700 px-4 py-2 text-sm font-semibold text-white">Record</button></div></div>
    </form>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b">
            <h3 class="font-semibold text-gray-800">Recent Transactions</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="responsive-table w-full text-sm">
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
                                <x-status-badge :status="$tx->type" />
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
                        <tr><td colspan="6"><x-empty-state icon="fa-wallet" title="No transactions yet" description="Transaction history will appear here." /></td></tr>
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
            <table class="responsive-table w-full text-sm">
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
                                <x-status-badge :status="$topUp->status" />
                            </td>
                            <td class="px-6 py-3 text-gray-700">{{ ucfirst($topUp->payment_method ?? 'N/A') }}</td>
                            <td class="px-6 py-3 text-gray-500 text-xs">{{ $topUp->created_at->format('M d, Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty-state icon="fa-wallet" title="No top-ups yet" description="Top-up history will appear here." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
