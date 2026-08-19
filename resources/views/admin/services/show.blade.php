@extends('admin.layouts.app')
@section('title', 'Service Details - LesGo Admin')
@section('header', 'Service: ' . $service->name)

@section('actions')
<a href="{{ route('admin.services.edit', $service) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-edit mr-1"></i> Edit</a>
<form action="{{ route('admin.services.toggle', $service) }}" method="POST" class="inline">
    @csrf
    <button type="submit" class="bg-{{ $service->is_active ? 'red' : 'green' }}-500 text-white px-4 py-2 rounded-lg text-sm">
        {{ $service->is_active ? 'Deactivate' : 'Activate' }}
    </button>
</form>
@endsection

@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="space-y-4 text-sm">
            <div class="flex justify-between border-b pb-3"><span class="text-gray-500">Name</span><span class="font-medium text-gray-800">{{ $service->name }}</span></div>
            <div class="flex justify-between border-b pb-3"><span class="text-gray-500">Code</span><span class="font-medium text-gray-800">{{ $service->code }}</span></div>
            <div class="flex justify-between border-b pb-3"><span class="text-gray-500">Partner</span><span class="font-medium text-gray-800">{{ $service->partner->name ?? 'N/A' }}</span></div>
            <div class="flex justify-between border-b pb-3"><span class="text-gray-500">Base Fare</span><span class="font-medium text-gray-800">₱{{ number_format($service->base_fare, 2) }}</span></div>
            <div class="flex justify-between border-b pb-3"><span class="text-gray-500">Per KM Rate</span><span class="font-medium text-gray-800">₱{{ number_format($service->per_km_rate, 2) }}</span></div>
            <div class="flex justify-between border-b pb-3"><span class="text-gray-500">Per Minute Rate</span><span class="font-medium text-gray-800">₱{{ number_format($service->per_minute_rate, 2) }}</span></div>
            <div class="flex justify-between border-b pb-3"><span class="text-gray-500">Minimum Fare</span><span class="font-medium text-gray-800">₱{{ number_format($service->minimum_fare, 2) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Active</span>
                @if($service->is_active)<span class="text-green-600 font-medium">Yes</span>@else<span class="text-red-600 font-medium">No</span>@endif
            </div>
        </div>
        @if($service->description)
            <div class="mt-4 pt-4 border-t">
                <p class="text-xs text-gray-500 mb-1">Description</p>
                <p class="text-sm text-gray-700">{{ $service->description }}</p>
            </div>
        @endif
    </div>
</div>
@endsection
