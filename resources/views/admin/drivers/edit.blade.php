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
                    <label class="block text-sm font-medium text-gray-700 mb-1">License Expiry</label>
                    <input type="date" name="license_expiry_date" value="{{ old('license_expiry_date', $driver->license_expiry_date?->format('Y-m-d')) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle Type</label>
                    <input type="text" name="vehicle_type" value="{{ old('vehicle_type', $driver->vehicle_type) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Plate Number</label>
                    <input type="text" name="plate_number" value="{{ old('plate_number', $driver->plate_number) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Package Tier</label>
                <input type="text" name="package_tier" value="{{ old('package_tier', $driver->package_tier) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2" placeholder="basic / premium / elite">
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg text-sm font-medium">Save Changes</button>
                <a href="{{ route('admin.drivers.show', $driver) }}" class="text-gray-500 hover:text-gray-700 text-sm">Cancel</a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6 mt-6">
        <h3 class="font-semibold text-gray-800 mb-4">Documents & Requirements</h3>
        @if($driver->id_document_path)<p class="text-xs text-gray-500 mb-2">Current: <a href="{{ Storage::disk(config('filesystems.default') === 's3' ? 's3' : 'public')->url($driver->id_document_path) }}" target="_blank" class="text-blue-600 underline">{{ $driver->id_document_path }}</a></p>@endif
        <form method="POST" action="{{ route('admin.drivers.documents.update', $driver) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ID Document</label>
                <input type="file" name="id_document" accept=".jpg,.jpeg,.png,.pdf" class="w-full text-sm border rounded-lg px-3 py-2">
                @if($driver->id_document_path)<label class="inline-flex items-center gap-2 mt-2 text-xs"><input type="checkbox" name="remove_id_document" value="1"> Remove current</label>@endif
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div><label class="block text-xs text-gray-500 mb-1">license_front</label><input type="file" name="documents[license_front]" accept=".jpg,.jpeg,.png,.pdf" class="w-full text-sm border rounded px-2 py-1">@if(data_get($driver->documents,'license_front'))<p class="text-xs text-green-600 truncate">{{ data_get($driver->documents,'license_front') }}</p>@endif</div>
                <div><label class="block text-xs text-gray-500 mb-1">or_cr</label><input type="file" name="documents[or_cr]" accept=".jpg,.jpeg,.png,.pdf" class="w-full text-sm border rounded px-2 py-1">@if(data_get($driver->documents,'or_cr'))<p class="text-xs text-green-600 truncate">{{ data_get($driver->documents,'or_cr') }}</p>@endif</div>
                <div><label class="block text-xs text-gray-500 mb-1">nbi_clearance</label><input type="file" name="documents[nbi_clearance]" accept=".jpg,.jpeg,.png,.pdf" class="w-full text-sm border rounded px-2 py-1">@if(data_get($driver->documents,'nbi_clearance'))<p class="text-xs text-green-600 truncate">{{ data_get($driver->documents,'nbi_clearance') }}</p>@endif</div>
                <div><label class="block text-xs text-gray-500 mb-1">vehicle_photo</label><input type="file" name="documents[vehicle_photo]" accept=".jpg,.jpeg,.png,.pdf" class="w-full text-sm border rounded px-2 py-1">@if(data_get($driver->documents,'vehicle_photo'))<p class="text-xs text-green-600 truncate">{{ data_get($driver->documents,'vehicle_photo') }}</p>@endif</div>
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">Save Documents</button>
        </form>
    </div>
</div>
@endsection
