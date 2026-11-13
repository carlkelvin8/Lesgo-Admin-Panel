@extends('admin.layouts.app')
@section('title', 'Create Service - LesGo Admin')
@section('header', 'Create Service')

@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <form method="POST" action="{{ route('admin.services.store') }}">
            @csrf

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
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="field-name">Name</label>
                    <input id="field-name" type="text" name="name" value="{{ old('name') }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="field-code">Code</label>
                    <input id="field-code" type="text" name="code" value="{{ old('code') }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1" for="field-description">Description</label>
                <textarea id="field-description" name="description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">{{ old('description') }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="field-base_fare">Base Fare (₱)</label>
                    <input id="field-base_fare" type="number" step="0.01" name="base_fare" value="{{ old('base_fare') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="field-per_km_rate">Per KM Rate (₱)</label>
                    <input id="field-per_km_rate" type="number" step="0.01" name="per_km_rate" value="{{ old('per_km_rate') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="field-per_minute_rate">Per Minute Rate (₱)</label>
                    <input id="field-per_minute_rate" type="number" step="0.01" name="per_minute_rate" value="{{ old('per_minute_rate') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="field-minimum_fare">Minimum Fare (₱)</label>
                    <input id="field-minimum_fare" type="number" step="0.01" name="minimum_fare" value="{{ old('minimum_fare') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg text-sm font-medium">Create Service</button>
                <a href="{{ route('admin.services.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
