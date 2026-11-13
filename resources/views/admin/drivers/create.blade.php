@extends('admin.layouts.app')
@section('title', 'Create Driver - LesGo Admin')
@section('header', 'Create Driver')

@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <form method="POST" action="{{ route('admin.drivers.store') }}">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1" for="field-user_id">User</label>
                <select id="field-user_id" name="user_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
                    <option value="">Select User</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }} ({{ $user->email }})</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1" for="field-partner_id">Partner</label>
                <select id="field-partner_id" name="partner_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
                    <option value="">None</option>
                    @foreach($partners as $partner)
                        <option value="{{ $partner->id }}" {{ old('partner_id') == $partner->id ? 'selected' : '' }}>{{ $partner->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="field-license_number">License Number</label>
                    <input id="field-license_number" type="text" name="license_number" value="{{ old('license_number') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="field-vehicle_type">Vehicle Type</label>
                    <input id="field-vehicle_type" type="text" name="vehicle_type" value="{{ old('vehicle_type') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="field-plate_number">Plate Number</label>
                    <input id="field-plate_number" type="text" name="plate_number" value="{{ old('plate_number') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="field-package_tier">Package Tier</label>
                    <select id="field-package_tier" name="package_tier" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
                        <option value="">None</option>
                        @foreach(['basic', 'premium', 'elite'] as $tier)
                            <option value="{{ $tier }}" {{ old('package_tier') === $tier ? 'selected' : '' }}>{{ ucfirst($tier) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg text-sm font-medium">Create Driver</button>
                <a href="{{ route('admin.drivers.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
