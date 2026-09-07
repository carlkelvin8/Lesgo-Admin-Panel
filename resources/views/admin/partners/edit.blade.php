@extends('admin.layouts.app')
@section('title', 'Edit Partner - LesGo Admin')
@section('header', 'Edit Partner')

@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <form method="POST" action="{{ route('admin.partners.update', $partner) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" name="name" value="{{ old('name', $partner->name) }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Legal Name</label>
                    <input type="text" name="legal_name" value="{{ old('legal_name', $partner->legal_name) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Business Type</label>
                    <input type="text" name="business_type" value="{{ old('business_type', $partner->business_type) }}" placeholder="e.g. restaurant, retail" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <input type="text" name="category" value="{{ old('category', $partner->category) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tax ID</label>
                    <input type="text" name="tax_id" value="{{ old('tax_id', $partner->tax_id) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Support Email</label>
                    <input type="email" name="support_email" value="{{ old('support_email', $partner->support_email) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Support Phone</label>
                <input type="text" name="support_phone" value="{{ old('support_phone', $partner->support_phone) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
                    @foreach(['pending', 'approved', 'rejected', 'suspended'] as $s)
                        <option value="{{ $s }}" {{ old('status', $partner->status) === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Delivery Fee (₱)</label>
                <input type="number" step="0.01" name="delivery_fee" value="{{ old('delivery_fee', $partner->delivery_fee) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">{{ old('description', $partner->description) }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Logo</label>
                    @if($partner->logo_url)<img src="{{ \Illuminate\Support\Str::startsWith($partner->logo_url, ['http://','https://']) ? $partner->logo_url : \Illuminate\Support\Facades\Storage::disk(config('filesystems.default') === 's3' ? 's3' : 'public')->url($partner->logo_url) }}" class="w-12 h-12 rounded-full object-cover mb-2 border"><label class="inline-flex items-center gap-1 text-xs"><input type="checkbox" name="remove_logo" value="1"> Remove</label>@endif
                    <input type="file" name="logo" accept=".jpg,.jpeg,.png,.webp" class="w-full text-sm border rounded-lg px-3 py-2 mt-1">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cover Image</label>
                    @if($partner->cover_image_url)<img src="{{ \Illuminate\Support\Str::startsWith($partner->cover_image_url, ['http://','https://']) ? $partner->cover_image_url : \Illuminate\Support\Facades\Storage::disk(config('filesystems.default') === 's3' ? 's3' : 'public')->url($partner->cover_image_url) }}" class="w-full h-20 object-cover rounded mb-2 border"><label class="inline-flex items-center gap-1 text-xs"><input type="checkbox" name="remove_cover" value="1"> Remove</label>@endif
                    <input type="file" name="cover_image" accept=".jpg,.jpeg,.png,.webp" class="w-full text-sm border rounded-lg px-3 py-2 mt-1">
                </div>
            </div>

            <div class="mb-4 flex gap-6">
                <label class="flex items-center gap-2"><input type="hidden" name="is_open" value="0"><input type="checkbox" name="is_open" value="1" {{ old('is_open', $partner->is_open) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600"><span class="text-sm text-gray-700">Open</span></label>
                <label class="flex items-center gap-2"><input type="hidden" name="is_featured" value="0"><input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $partner->is_featured) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600"><span class="text-sm text-gray-700">Featured</span></label>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg text-sm font-medium">Save Changes</button>
                <a href="{{ route('admin.partners.show', $partner) }}" class="text-gray-500 hover:text-gray-700 text-sm">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
