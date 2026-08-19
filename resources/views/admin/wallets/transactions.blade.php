@extends('admin.layouts.app')
@section('title', 'Wallet Transactions - LesGo Admin')
@section('header', 'Wallet Transactions')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.wallets.show', $wallet) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium"><i class="fas fa-arrow-left mr-1"></i> Back to Wallet</a>
</div>

<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div>
            <select name="type" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">All Types</option>
                @foreach(['credit','debit'] as $t)
                    <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                @endforeach
            </select>
        </div>
        <div><input type="date" name="date_from" value="{{ request('date_from') }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="From"></div>
        <div><input type="date" name="date_to" value="{{ request('date_to') }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="To"></div>
        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-700"><i class="fas fa-filter mr-1"></i> Filter</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Type</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Amount</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Balance Before</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Balance After</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Description</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Reference</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($transactions as $tx)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            @php
                                $tc = [
                                    'credit' => 'bg-green-100 text-green-800',
                                    'debit' => 'bg-red-100 text-red-800',
                                ];
                            @endphp
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $tc[$tx->type] ?? 'bg-gray-100 text-gray-800' }}">{{ ucfirst($tx->type) }}</span>
                        </td>
                        <td class="px-6 py-4 font-medium {{ $tx->type === 'credit' ? 'text-green-600' : 'text-red-600' }}">
                            {{ $tx->type === 'credit' ? '+' : '-' }}₱{{ number_format($tx->amount, 2) }}
                        </td>
                        <td class="px-6 py-4 text-gray-500">₱{{ number_format($tx->balance_before, 2) }}</td>
                        <td class="px-6 py-4 text-gray-500">₱{{ number_format($tx->balance_after, 2) }}</td>
                        <td class="px-6 py-4 text-gray-700 text-xs max-w-[200px] truncate">{{ $tx->description ?? '—' }}</td>
                        <td class="px-6 py-4 text-gray-500 text-xs">{{ $tx->reference ?? '—' }}</td>
                        <td class="px-6 py-4 text-gray-500 text-xs">{{ $tx->created_at->format('M d, Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-6 py-12 text-center text-gray-400">No transactions found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t">{{ $transactions->links() }}</div>
</div>
@endsection
