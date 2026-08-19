@extends('admin.layouts.app')
@section('title', 'Edit Driver - LesGo Admin')
@section('header', 'Edit Driver')

@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <form method="POST" action="{{ route('admin.drivers.update', $driver) }}">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    @foreach(['pending','active','inactive','suspended'] as $s)
                        <option value="{{ $s }}" {{ old('status', $driver->status) === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">License Number</label>
                    <input type="text" name="license_number" value="{{ old('license_number', $driver->license_number) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Package Tier</label>
                    <input type="text" name="package_tier" value="{{ old('package_tier', $driver->package_tier) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle Type</label>
                    <input type="text" name="vehicle_type" value="{{ old('vehicle_type', $driver->vehicle_type) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle Make</label>
                    <input type="text" name="vehicle_make" value="{{ old('vehicle_make', $driver->vehicle_make) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle Model</label>
                    <input type="text" name="vehicle_model" value="{{ old('vehicle_model', $driver->vehicle_model) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle Color</label>
                    <input type="text" name="vehicle_color" value="{{ old('vehicle_color', $driver->vehicle_color) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Plate Number</label>
                <input type="text" name="vehicle_plate_number" value="{{ old('vehicle_plate_number', $driver->vehicle_plate_number) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg text-sm font-medium">Save Changes</button>
                <a href="{{ route('admin.drivers.show', $driver) }}" class="text-gray-500 hover:text-gray-700 text-sm">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
