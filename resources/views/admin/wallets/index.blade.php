@extends('admin.layouts.app')
@section('title', 'Wallets - LesGo Admin')
@section('header', 'Wallet Management')

@section('content')
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <x-filter-panel action="{{ request()->url() }}">
        <x-filter-input name="search" label="Search" placeholder="Search by user name or email..." value="{{ request('search') }}" />
    </x-filter-panel>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="responsive-table w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">User</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Balance</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Currency</th>
                    <th class="text-left px-6 py-3 text-gray-500 font-medium">Last Transaction</th>
                    <th class="text-right px-6 py-3 text-gray-500 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($wallets as $wallet)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-800">{{ $wallet->user->name ?? 'N/A' }}</div>
                            <div class="text-xs text-gray-500">{{ $wallet->user->email ?? '' }}</div>
                        </td>
                        <td class="px-6 py-4 font-semibold text-gray-800">₱{{ number_format($wallet->balance, 2) }}</td>
                        <td class="px-6 py-4 text-gray-700">{{ $wallet->currency }}</td>
                        <td class="px-6 py-4 text-gray-500 text-xs">
                            @php $lastTx = $wallet->transactions->first(); @endphp
                            {{ $lastTx ? $lastTx->created_at->format('M d, Y H:i') : '—' }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.wallets.show', $wallet) }}" class="text-blue-600 hover:text-blue-800"><i class="fas fa-eye"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty-state icon="fa-wallet" title="No wallets found" description="Wallets will appear here once users create them." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t">{{ $wallets->links() }}</div>
</div>
@endsection
