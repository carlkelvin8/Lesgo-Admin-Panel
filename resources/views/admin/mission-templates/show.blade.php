@extends('admin.layouts.app')
@section('title', 'Mission Details - LesGo Admin')
@section('header', 'Mission: ' . $missionTemplate->title)

@section('actions')
<a href="{{ route('admin.mission-templates.edit', $missionTemplate) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-edit mr-1"></i> Edit</a>
<button type="button" class="bg-{{ $missionTemplate->is_active ? 'red' : 'green' }}-500 text-white px-4 py-2 rounded-lg text-sm"
    x-data
    @click="$dispatch('confirm-modal', {
        title: '{{ $missionTemplate->is_active ? 'Deactivate' : 'Activate' }} Mission',
        message: 'Are you sure you want to {{ $missionTemplate->is_active ? 'deactivate' : 'activate' }} {{ addslashes($missionTemplate->title) }}?',
        confirmText: '{{ $missionTemplate->is_active ? 'Deactivate' : 'Activate' }}',
        confirmClass: '{{ $missionTemplate->is_active ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700' }}',
        onConfirm: () => {
            const f=document.createElement('form');f.method='POST';f.action='{{ route('admin.mission-templates.toggle', $missionTemplate) }}';
            const i1=document.createElement('input');i1.type='hidden';i1.name='_token';i1.value='{{ csrf_token() }}';f.appendChild(i1);
            document.body.appendChild(f);f.submit();
        }
    })">
    {{ $missionTemplate->is_active ? 'Deactivate' : 'Activate' }}
</button>
@endsection

@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="space-y-4 text-sm">
            <div class="flex justify-between border-b pb-3"><span class="text-gray-500">Title</span><span class="font-medium text-gray-800">{{ $missionTemplate->title }}</span></div>
            <div class="flex justify-between border-b pb-3"><span class="text-gray-500">Audience</span><span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700">{{ $missionTemplate->target_audience }}</span></div>
            <div class="flex justify-between border-b pb-3"><span class="text-gray-500">Type</span><span class="font-medium">{{ $missionTemplate->type }}</span></div>
            <div class="flex justify-between border-b pb-3"><span class="text-gray-500">Goal Type</span><span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">{{ $missionTemplate->goal_type }}</span></div>
            <div class="flex justify-between border-b pb-3"><span class="text-gray-500">Goal Target</span><span class="font-medium">{{ $missionTemplate->goal_target }}</span></div>
            <div class="flex justify-between border-b pb-3"><span class="text-gray-500">Reward</span><span class="font-medium">₱{{ number_format($missionTemplate->reward_amount,2) }} {{ $missionTemplate->reward_currency }}</span></div>
            <div class="flex justify-between border-b pb-3"><span class="text-gray-500">Service Code</span><span class="font-medium">{{ $missionTemplate->service_code ?? 'N/A' }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Active</span>
                @if($missionTemplate->is_active)<span class="text-green-600 font-medium">Yes</span>@else<span class="text-red-600 font-medium">No</span>@endif
            </div>
        </div>
        @if($missionTemplate->description)
            <div class="mt-4 pt-4 border-t">
                <p class="text-xs text-gray-500 mb-1">Description</p>
                <p class="text-sm text-gray-700">{{ $missionTemplate->description }}</p>
            </div>
        @endif

        <div class="mt-6 flex gap-3">
            <form method="POST" action="{{ route('admin.mission-templates.destroy', $missionTemplate) }}" onsubmit="return confirm('Delete this mission? This will not delete already assigned user missions but template will be gone.')">
                @csrf @method('DELETE')
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm">Delete</button>
            </form>
        </div>
    </div>
</div>
@endsection
