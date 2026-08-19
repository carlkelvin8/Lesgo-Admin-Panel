@extends('admin.layouts.app')
@section('title', 'FAQ Categories - LesGo Admin')
@section('header', 'FAQ Categories')

@section('actions')
<a href="{{ route('admin.faq.articles') }}" class="border border-gray-300 px-4 py-2 rounded-lg text-sm">View Articles</a>
<a href="{{ route('admin.faq.categories.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-plus mr-1"></i> Add Category</a>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b"><tr><th class="text-left px-6 py-3">Category</th><th class="text-left px-6 py-3">Articles</th><th class="text-left px-6 py-3">Order</th><th class="text-left px-6 py-3">Status</th><th class="px-6 py-3"></th></tr></thead>
        <tbody class="divide-y">
            @forelse($categories as $category)
            <tr>
                <td class="px-6 py-4"><p class="font-medium">{{ $category->icon }} {{ $category->name }}</p><p class="text-xs text-gray-500">{{ $category->description ?: 'No description' }}</p></td>
                <td class="px-6 py-4">{{ $category->articles_count }}</td><td class="px-6 py-4">{{ $category->sort_order }}</td>
                <td class="px-6 py-4"><span class="px-2 py-1 rounded-full text-xs {{ $category->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">{{ $category->is_active ? 'Active' : 'Inactive' }}</span></td>
                <td class="px-6 py-4"><div class="flex justify-end gap-3"><a href="{{ route('admin.faq.categories.edit', $category) }}" class="text-blue-600">Edit</a><form method="POST" action="{{ route('admin.faq.categories.destroy', $category) }}" onsubmit="return confirm('Delete this category and all its articles?')">@csrf @method('DELETE')<button class="text-red-600">Delete</button></form></div></td>
            </tr>
            @empty<tr><td colspan="5" class="px-6 py-10 text-center text-gray-400">No FAQ categories yet.</td></tr>@endforelse
        </tbody>
    </table>
    @if($categories->hasPages())<div class="px-6 py-4 border-t">{{ $categories->links() }}</div>@endif
</div>
@endsection
