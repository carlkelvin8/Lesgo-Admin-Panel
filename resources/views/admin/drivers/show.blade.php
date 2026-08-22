@extends('admin.layouts.app')
@section('title', 'Driver Details - LesGo Admin')
@section('header', 'Driver Details')

@section('actions')
<a href="{{ route('admin.drivers.edit', $driver) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-edit mr-1"></i> Edit</a>
<form action="{{ route('admin.drivers.toggle', $driver) }}" method="POST" class="inline">
    @csrf
    <button type="submit" class="bg-{{ $driver->status === 'active' ? 'red' : 'green' }}-500 text-white px-4 py-2 rounded-lg text-sm">
        <i class="fas fa-{{ $driver->status === 'active' ? 'ban' : 'check' }} mr-1"></i> {{ $driver->status === 'active' ? 'Deactivate' : 'Activate' }}
    </button>
</form>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="text-center mb-4">
            <div class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center font-bold text-2xl mx-auto mb-4">
                {{ substr($driver->user->name ?? '?', 0, 1) }}
            </div>
            <h3 class="text-xl font-bold text-gray-800">{{ $driver->user->name ?? 'N/A' }}</h3>
            <p class="text-gray-500 text-sm">{{ $driver->user->email ?? '' }}</p>
            <x-status-badge status="{{ $driver->status }}" />
        </div>
        <div class="space-y-3 text-sm">
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Rating</span><span>{{ $driver->rating }} <i class="fas fa-star text-yellow-400 text-xs"></i></span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Total Trips</span><span>{{ $driver->total_trips }}</span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">License #</span><span>{{ $driver->license_number ?? '-' }}</span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">License Expiry</span><span>{{ $driver->license_expiry_date ? $driver->license_expiry_date->format('M d, Y') : '-' }}</span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Package Tier</span><span>{{ $driver->package_tier ?? '-' }}</span></div>
        </div>
    </div>

    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Vehicle Information</h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div><p class="text-gray-500">Type</p><p class="font-medium">{{ $driver->vehicle_type ?? '-' }}</p></div>
                <div><p class="text-gray-500">Plate #</p><p class="font-medium">{{ $driver->plate_number ?? '-' }}</p></div>
                <div><p class="text-gray-500">Last Location</p><p class="font-medium text-xs">{{ $driver->last_latitude ? $driver->last_latitude . ', ' . $driver->last_longitude : '-' }}</p></div>
            </div>
        </div>
    </div>
</div>
@endsection
