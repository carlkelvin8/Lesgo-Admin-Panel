@extends('admin.layouts.app')
@section('title', 'Mission Reward Reimbursements - LesGo Admin')
@section('header', 'Mission Reward Reimbursements (PayMongo)')

@section('content')
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-4 text-center">
        <div class="bg-gray-50 p-3 rounded-lg"><div class="text-xs text-gray-500">Total</div><div class="text-xl font-bold">{{ $stats['total'] }}</div></div>
        <div class="bg-yellow-50 p-3 rounded-lg"><div class="text-xs text-gray-500">Pending</div><div class="text-xl font-bold text-yellow-600">{{ $stats['pending'] }}</div></div>
        <div class="bg-blue-50 p-3 rounded-lg"><div class="text-xs text-gray-500">Processing</div><div class="text-xl font-bold text-blue-600">{{ $stats['processing'] }}</div></div>
        <div class="bg-green-50 p-3 rounded-lg"><div class="text-xs text-gray-500">Successful</div><div class="text-xl font-bold text-green-600">{{ $stats['successful'] }}</div></div>
        <div class="bg-red-50 p-3 rounded-lg"><div class="text-xs text-gray-500">Failed</div><div class="text-xl font-bold text-red-600">{{ $stats['failed'] }}</div></div>
    </div>
    <x-filter-panel action="{{ request()->url() }}">
        <x-filter-input name="search" label="Search" placeholder="Ref ID or rider name..." />
        <x-filter-input name="status" label="Status" type="select" :options="['' => 'All', 'pending' => 'Pending', 'processing' => 'Processing', 'successful' => 'Successful', 'failed' => 'Failed']" />
    </x-filter-panel>
    <div class="text-xs text-gray-500 mt-2">All reimbursements are funded from LesGo's PayMongo balance. Rider wallet is credited only after webhook confirmation.</div>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="responsive-table w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">Payout ID</th>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">Rider</th>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">Mission</th>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">Amount</th>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">PayMongo Ref</th>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">Status</th>
                    <th class="text-left px-4 py-3 text-gray-500 font-medium">Date</th>
                    <th class="text-right px-4 py-3 text-gray-500 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($payouts as $p)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-xs">#{{ $p->id }} <div class="text-gray-400">{{ $p->idempotency_key }}</div></td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-800">{{ $p->rider?->name ?? 'Rider #'.$p->rider_user_id }}</div>
                            <div class="text-xs text-gray-500">{{ $p->rider?->email }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-xs font-medium">{{ $p->missionTemplate?->title ?? 'Mission #'.$p->mission_id }}</div>
                            <div class="text-xs text-gray-400">Template #{{ $p->mission_template_id }}</div>
                        </td>
                        <td class="px-4 py-3 font-semibold">₱{{ number_format($p->reward_amount,2) }}</td>
                        <td class="px-4 py-3 font-mono text-xs">
                            <div class="truncate max-w-[160px]">{{ $p->paymongo_reference ?? '—' }}</div>
                            <div class="text-gray-400 truncate max-w-[160px]">{{ $p->paymongo_transfer_id ?? '' }}</div>
                        </td>
                        <td class="px-4 py-3">
                            @if($p->status==='pending') <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-700">Pending</span>
                            @elseif($p->status==='processing') <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700">Processing</span>
                            @elseif($p->status==='successful') <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-700">Successful</span>
                            @else <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-700">Failed</span>
                                @if($p->failure_reason)<div class="text-xs text-red-500 max-w-[200px] truncate" title="{{ $p->failure_reason }}">{{ $p->failure_reason }}</div>@endif
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500">{{ $p->created_at->format('M d, Y H:i') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.mission-rewards.show', $p) }}" class="text-blue-600 hover:text-blue-800 mr-2"><i class="fas fa-eye"></i></a>
                            @if($p->status==='failed')
                                <form action="{{ route('admin.mission-rewards.retry', $p) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-orange-600 hover:text-orange-800" title="Retry via PayMongo" onclick="return confirm('Retry this reimbursement? This will initiate a new PayMongo transfer.')"><i class="fas fa-redo"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-empty-state icon="fa-money-bill-transfer" title="No reimbursements found" description="Mission reward reimbursements will appear here after riders claim rewards." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t">{{ $payouts->links() }}</div>
</div>
@endsection
