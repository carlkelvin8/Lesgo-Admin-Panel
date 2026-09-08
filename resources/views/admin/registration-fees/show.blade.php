@extends('admin.layouts.app')
@section('title', 'Registration Fee #'.$fee->id.' - LesGo Admin')
@section('header', 'Registration Fee Details — '.ucfirst($fee->account_type).' #'.$fee->id)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    @if(session('success'))<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">{{ session('error') }}</div>@endif

    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div><span class="text-gray-500">User:</span> <span class="font-medium">{{ $fee->user?->name }} ({{ $fee->user?->email }}) — ID {{ $fee->user_id }}</span></div>
            <div><span class="text-gray-500">Account Type:</span> <span class="font-mono">{{ $fee->account_type }}</span></div>
            <div><span class="text-gray-500">Amount:</span> <span class="font-bold text-lg">₱{{ number_format($fee->amount,2) }} {{ $fee->currency }}</span></div>
            <div><span class="text-gray-500">Account Status:</span> <span class="px-2 py-1 text-xs rounded-full {{ $fee->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $fee->is_active ? 'Active' : 'Restricted' }}</span> <span class="text-xs text-gray-500">{{ $fee->is_active ? 'Paid + Approved' : 'Requires fee + approval' }}</span></div>
            <div><span class="text-gray-500">Payment Status:</span> <span class="px-2 py-1 text-xs rounded-full {{ $fee->payment_status==='paid' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">{{ ucfirst($fee->payment_status) }}</span></div>
            <div><span class="text-gray-500">Application Status:</span> <span class="px-2 py-1 text-xs rounded-full {{ $fee->application_status==='approved' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">{{ ucfirst($fee->application_status) }}</span></div>
            <div><span class="text-gray-500">PayMongo Reference:</span> <span class="font-mono text-xs break-all">{{ $fee->paymongo_reference }}</span></div>
            <div><span class="text-gray-500">Checkout ID:</span> <span class="font-mono text-xs break-all">{{ $fee->paymongo_checkout_id ?? '—' }}</span></div>
            <div><span class="text-gray-500">Checkout URL:</span> @if($fee->checkout_url)<a href="{{ $fee->checkout_url }}" target="_blank" class="text-blue-600 hover:underline text-xs break-all">{{ $fee->checkout_url }}</a>@else — @endif</div>
            <div><span class="text-gray-500">Payment Date:</span> {{ $fee->payment_date?->format('Y-m-d H:i:s') ?? '—' }}</div>
            <div><span class="text-gray-500">Approved At:</span> {{ $fee->approved_at?->format('Y-m-d H:i:s') ?? '—' }} @if($fee->approver) by {{ $fee->approver->name }} @endif</div>
            <div><span class="text-gray-500">Activated At:</span> {{ $fee->activated_at?->format('Y-m-d H:i:s') ?? '—' }}</div>
            @if($fee->failure_reason)<div class="col-span-2"><span class="text-gray-500">Failure Reason:</span> <span class="text-red-600">{{ $fee->failure_reason }}</span></div>@endif
            @if($fee->is_grandfathered)<div class="col-span-2"><span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600">Grandfathered account (active before fee enforcement)</span></div>@endif
        </div>

        <div class="mt-6 flex gap-2">
            @if($fee->application_status !== 'approved')
                <form method="POST" action="{{ route('admin.registration-fees.approve', $fee) }}">@csrf<button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg" onclick="return confirm('Approve? Account will only activate if fee PAID. Approval does not bypass unpaid fee.')">Approve Application</button></form>
            @endif
            @if($fee->application_status !== 'rejected')
                <form method="POST" action="{{ route('admin.registration-fees.reject', $fee) }}">@csrf<input type="hidden" name="reason" value="Rejected by admin via detail page"><button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg" onclick="return confirm('Reject application? Account will remain restricted.')">Reject Application</button></form>
            @endif
        </div>

        <div class="mt-6 pt-6 border-t">
            <h4 class="font-medium mb-2">Keys never exposed to frontend</h4>
            <p class="text-xs text-gray-500">PayMongo secret keys are not shown here. Only reference IDs and statuses are visible.</p>
        </div>

        <div class="mt-6">
            <a href="{{ route('admin.registration-fees.index') }}" class="text-blue-600 hover:text-blue-800 text-sm"><i class="fas fa-arrow-left mr-1"></i> Back to registration fees</a>
        </div>
    </div>
</div>
@endsection
