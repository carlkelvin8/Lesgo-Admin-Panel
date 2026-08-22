@extends('admin.layouts.app')
@section('title', 'Wallet Top-ups - LesGo Admin')
@section('header', 'Wallet Top-up Review')

@section('actions')
<a href="{{ route('admin.wallets.index') }}" class="rounded-lg border bg-white px-4 py-2 text-sm text-gray-700">All Wallets</a>
@endsection

@section('content')
<div class="mb-6 rounded-xl bg-white p-4 shadow-sm">
    <x-filter-panel action="{{ request()->url() }}">
        <x-filter-input name="search" label="Search" placeholder="User, external ID, or gateway reference" value="{{ request('search') }}" />
        <x-filter-input name="status" label="Status" type="select" :options="['' => 'All', 'pending' => 'Pending', 'paid' => 'Paid', 'failed' => 'Failed', 'expired' => 'Expired']" :selected="request('status')" />
        <x-filter-input name="provider" label="Provider" placeholder="e.g. xendit" value="{{ request('provider') }}" />
    </x-filter-panel>
</div>

<div class="overflow-hidden rounded-xl bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="responsive-table w-full text-sm">
            <thead class="border-b bg-gray-50"><tr><th class="px-5 py-3 text-left font-medium text-gray-500">User</th><th class="px-5 py-3 text-left font-medium text-gray-500">Reference</th><th class="px-5 py-3 text-left font-medium text-gray-500">Amount</th><th class="px-5 py-3 text-left font-medium text-gray-500">Provider</th><th class="px-5 py-3 text-left font-medium text-gray-500">Status</th><th class="px-5 py-3 text-left font-medium text-gray-500">Review</th></tr></thead>
            <tbody class="divide-y">
                @forelse($topUps as $topUp)
                    <tr class="align-top hover:bg-gray-50">
                        <td class="px-5 py-4"><a href="{{ route('admin.wallets.show', $topUp->wallet_id) }}" class="font-medium text-blue-700">{{ $topUp->user->name ?? 'Deleted user' }}</a><p class="text-xs text-gray-500">{{ $topUp->user->email ?? '—' }}</p></td>
                        <td class="px-5 py-4"><p class="font-mono text-xs">{{ $topUp->external_id }}</p><p class="mt-1 text-xs text-gray-400">{{ $topUp->gateway_reference ?: 'No gateway reference' }}</p></td>
                        <td class="px-5 py-4 font-semibold">₱{{ number_format($topUp->amount, 2) }}<p class="text-xs font-normal text-gray-400">Fee ₱{{ number_format($topUp->fee, 2) }}</p></td>
                        <td class="px-5 py-4">{{ strtoupper($topUp->provider ?: $topUp->payment_method) }}</td>
                        <td class="px-5 py-4"><x-status-badge :status="$topUp->status" />@if($topUp->reviewed_at)<p class="mt-2 text-xs text-gray-400">{{ $topUp->reviewer->name ?? 'Admin' }} · {{ $topUp->reviewed_at->format('M d, H:i') }}</p>@endif</td>
                        <td class="px-5 py-4">
                            @if($topUp->status === 'pending' && auth()->user()->hasAdminPermission('wallets.manage'))
                                <div class="flex flex-wrap gap-2">
                                    <form method="POST" action="{{ route('admin.wallets.top-ups.review', $topUp) }}" onsubmit="return confirm('Approve and credit this wallet top-up?')">@csrf<input type="hidden" name="decision" value="approve"><button class="rounded-lg bg-green-600 px-3 py-1.5 text-xs font-semibold text-white">Approve</button></form>
                                    <details class="relative"><summary class="cursor-pointer list-none rounded-lg bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700">Reject</summary><form method="POST" action="{{ route('admin.wallets.top-ups.review', $topUp) }}" class="absolute right-0 z-10 mt-2 w-72 rounded-xl border bg-white p-4 shadow-xl">@csrf<input type="hidden" name="decision" value="reject"><label class="mb-1 block text-xs font-medium">Rejection reason</label><textarea name="review_notes" required rows="3" class="w-full rounded-lg border px-3 py-2 text-sm"></textarea><button class="mt-3 rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white">Confirm rejection</button></form></details>
                                </div>
                            @else
                                <p class="max-w-xs text-xs text-gray-500">{{ $topUp->review_notes ?: 'No action available' }}</p>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty-state icon="fa-wallet" title="No wallet top-ups found" description="Top-up requests will appear here for review." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t px-6 py-4">{{ $topUps->links() }}</div>
</div>
@endsection
