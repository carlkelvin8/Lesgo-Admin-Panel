@extends('admin.layouts.app')
@section('title', 'Wallet Transactions - LesGo Admin')
@section('header', 'Wallet Transactions')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.wallets.show', $wallet) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium"><i class="fas fa-arrow-left mr-1"></i> Back to Wallet</a>
</div>

<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <x-filter-panel action="{{ request()->url() }}">
        <x-filter-input name="type" label="Type" type="select" :options="['' => 'All Types', 'credit' => 'Credit', 'debit' => 'Debit']" :selected="request('type')" />
        <x-filter-input name="date_from" label="From" type="date" value="{{ request('date_from') }}" />
        <x-filter-input name="date_to" label="To" type="date" value="{{ request('date_to') }}" />
    </x-filter-panel>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="responsive-table w-full text-sm">
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
                                <x-status-badge :status="$tx->type" />
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
                    <tr><td colspan="7"><x-empty-state icon="fa-wallet" title="No transactions found" description="Wallet transactions will appear here." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t">{{ $transactions->links() }}</div>
</div>
@endsection
