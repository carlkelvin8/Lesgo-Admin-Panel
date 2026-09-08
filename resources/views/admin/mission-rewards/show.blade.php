@extends('admin.layouts.app')
@section('title', 'Reimbursement #'.$payout->id.' - LesGo Admin')
@section('header', 'Reimbursement Details — Payout #'.$payout->id)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    @if(session('success'))<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">{{ session('error') }}</div>@endif

    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div><span class="text-gray-500">Rider:</span> <span class="font-medium">{{ $payout->rider?->name }} ({{ $payout->rider?->email }}) — ID {{ $payout->rider_user_id }}</span></div>
            <div><span class="text-gray-500">Driver Profile:</span> <span class="font-medium">#{{ $payout->driver_profile_id }}</span></div>
            <div><span class="text-gray-500">Mission:</span> <span class="font-medium">#{{ $payout->mission_id }} — {{ $payout->missionTemplate?->title }}</span></div>
            <div><span class="text-gray-500">Template:</span> <span class="font-medium">#{{ $payout->mission_template_id }} (Reward matches admin config: ₱{{ number_format($payout->missionTemplate?->reward_amount ?? $payout->reward_amount,2) }})</span></div>
            <div><span class="text-gray-500">Reward Amount:</span> <span class="font-bold text-lg">₱{{ number_format($payout->reward_amount,2) }} {{ $payout->reward_currency }}</span></div>
            <div><span class="text-gray-500">Provider:</span> <span class="font-mono">{{ $payout->provider }}</span> (PayMongo wallet: LesGo Platform)</div>
            <div><span class="text-gray-500">Status:</span>
                @if($payout->status==='pending') <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-700">Pending</span>
                @elseif($payout->status==='processing') <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700">Processing</span>
                @elseif($payout->status==='successful') <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-700">Successful</span>
                @else <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-700">Failed</span>@endif
            </div>
            <div><span class="text-gray-500">Attempts:</span> {{ $payout->attempts }}</div>
            <div><span class="text-gray-500">Idempotency Key:</span> <span class="font-mono text-xs">{{ $payout->idempotency_key }}</span></div>
            <div><span class="text-gray-500">PayMongo Reference:</span> <span class="font-mono text-xs">{{ $payout->paymongo_reference ?? '—' }}</span></div>
            <div><span class="text-gray-500">PayMongo Transfer ID:</span> <span class="font-mono text-xs">{{ $payout->paymongo_transfer_id ?? '—' }}</span></div>
            <div><span class="text-gray-500">Requested At:</span> {{ $payout->requested_at?->format('Y-m-d H:i:s') ?? $payout->created_at->format('Y-m-d H:i:s') }}</div>
            <div><span class="text-gray-500">Processed At:</span> {{ $payout->processed_at?->format('Y-m-d H:i:s') ?? '—' }}</div>
            @if($payout->failure_reason)<div class="col-span-2"><span class="text-gray-500">Failure Reason:</span> <span class="text-red-600">{{ $payout->failure_reason }}</span></div>@endif
        </div>

        @if($payout->status==='failed')
            <form action="{{ route('admin.mission-rewards.retry', $payout) }}" method="POST" class="mt-6">
                @csrf
                <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-2 rounded-lg" onclick="return confirm('Retry this reimbursement via PayMongo? This will not create duplicates.')">Retry Reimbursement via PayMongo</button>
                <span class="text-xs text-gray-500 ml-2">Duplicate-safe: same mission will never be paid twice.</span>
            </form>
        @endif

        <div class="mt-6 pt-6 border-t">
            <h4 class="font-medium mb-2">PayMongo Payload / Response (debug)</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <div class="text-xs text-gray-500">Payload</div>
                    <pre class="bg-gray-50 p-3 rounded text-xs overflow-auto max-h-64">{{ json_encode($payout->paymongo_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '—' }}</pre>
                </div>
                <div>
                    <div class="text-xs text-gray-500">Response / Webhook</div>
                    <pre class="bg-gray-50 p-3 rounded text-xs overflow-auto max-h-64">{{ json_encode($payout->paymongo_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '—' }}</pre>
                </div>
            </div>
            <div class="text-xs text-gray-400 mt-2">Secret keys are never displayed in admin panel.</div>
        </div>

        <div class="mt-6">
            <a href="{{ route('admin.mission-rewards.index') }}" class="text-blue-600 hover:text-blue-800 text-sm"><i class="fas fa-arrow-left mr-1"></i> Back to reimbursements</a>
        </div>
    </div>
</div>
@endsection
