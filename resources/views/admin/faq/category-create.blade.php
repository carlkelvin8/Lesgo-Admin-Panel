@extends('admin.layouts.app')
@php($editing = isset($category))
@section('title', ($editing ? 'Edit' : 'Add') . ' FAQ Category - LesGo Admin')
@section('header', ($editing ? 'Edit' : 'Add') . ' FAQ Category')

@section('content')
<form method="POST" action="{{ $editing ? route('admin.faq.categories.update', $category) : route('admin.faq.categories.store') }}" class="max-w-2xl bg-white rounded-xl shadow-sm p-6 space-y-5">
    @csrf @if($editing) @method('PUT') @endif
    <div><label class="block text-sm font-medium mb-1">Name</label><input name="name" value="{{ old('name', $category->name ?? '') }}" required class="w-full border rounded-lg px-3 py-2"></div>
    <div><label class="block text-sm font-medium mb-1">Icon <span class="font-normal text-gray-500">(emoji or Font Awesome label)</span></label><input name="icon" value="{{ old('icon', $category->icon ?? '') }}" maxlength="50" class="w-full border rounded-lg px-3 py-2"></div>
    <div><label class="block text-sm font-medium mb-1">Description</label><textarea name="description" rows="4" class="w-full border rounded-lg px-3 py-2">{{ old('description', $category->description ?? '') }}</textarea></div>
    <div class="grid grid-cols-2 gap-4"><div><label class="block text-sm font-medium mb-1">Sort order</label><input type="number" min="0" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}" class="w-full border rounded-lg px-3 py-2"></div><label class="flex items-center gap-2 mt-7"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active ?? true))> Active</label></div>
    <div class="flex gap-3"><button class="bg-blue-600 text-white px-5 py-2 rounded-lg">{{ $editing ? 'Save Changes' : 'Create Category' }}</button><a href="{{ route('admin.faq.categories') }}" class="border px-5 py-2 rounded-lg">Cancel</a></div>
</form>
@endsection
