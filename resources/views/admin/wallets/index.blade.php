@extends('admin.layouts.app')
@section('title', 'Wallets - LesGo Admin')
@section('header', 'Wallet Management')

@section('content')
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[200px]">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by user name or email..."
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-blue-500 outline-none">
        </div>
        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-700"><i class="fas fa-filter mr-1"></i> Filter</button>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
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
                    <tr><td colspan="5" class="px-6 py-12 text-center text-gray-400">No wallets found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t">{{ $wallets->links() }}</div>
</div>
@endsection
