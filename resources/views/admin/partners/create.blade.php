@extends('admin.layouts.app')
@section('title', 'Create Partner - LesGo Admin')
@section('header', 'Create Partner')

@section('content')
<div class="max-w-2xl">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <form method="POST" action="{{ route('admin.partners.store') }}">
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
                <label class="block text-sm font-medium text-gray-700 mb-1" for="field-name">Name</label>
                <input id="field-name" type="text" name="name" value="{{ old('name') }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1" for="field-category">Category</label>
                <input id="field-category" type="text" name="category" value="{{ old('category') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1" for="field-description">Description</label>
                <textarea id="field-description" name="description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">{{ old('description') }}</textarea>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1" for="field-delivery_fee">Delivery Fee (₱)</label>
                <input id="field-delivery_fee" type="number" step="0.01" name="delivery_fee" value="{{ old('delivery_fee') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-blue-500 outline-none">
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg text-sm font-medium">Create Partner</button>
                <a href="{{ route('admin.partners.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
