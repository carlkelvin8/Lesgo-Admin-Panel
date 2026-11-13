@extends('admin.layouts.app')
@section('title', 'Edit Partner - LesGo Admin')
@section('header', 'Edit Partner')

@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <form method="POST" action="{{ route('admin.partners.update', $partner) }}">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1" for="field-name">Name</label>
                <input id="field-name" type="text" name="name" value="{{ old('name', $partner->name) }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1" for="field-category">Category</label>
                <input id="field-category" type="text" name="category" value="{{ old('category', $partner->category) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1" for="field-status">Status</label>
                <select id="field-status" name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
                    @foreach(\App\Models\Partner::STATUSES as $s)
                        <option value="{{ $s }}" {{ old('status', $partner->status) === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1" for="field-delivery_fee">Delivery Fee (₱)</label>
                <input id="field-delivery_fee" type="number" step="0.01" name="delivery_fee" value="{{ old('delivery_fee', $partner->delivery_fee) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1" for="field-description">Description</label>
                <textarea id="field-description" name="description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">{{ old('description', $partner->description) }}</textarea>
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
