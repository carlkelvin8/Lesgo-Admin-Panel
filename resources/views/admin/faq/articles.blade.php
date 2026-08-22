@extends('admin.layouts.app')
@section('title', 'FAQ Articles - LesGo Admin')
@section('header', 'FAQ Knowledge Base')

@section('actions')
<a href="{{ route('admin.faq.categories') }}" class="border border-gray-300 px-4 py-2 rounded-lg text-sm">Categories</a>
<a href="{{ route('admin.faq.articles.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-plus mr-1"></i> Add Article</a>
@endsection

@section('content')
<x-filter-panel action="{{ request()->url() }}">
    <x-filter-input name="category_id" label="Category" type="select" :options="['' => 'All categories'] + $categories->pluck('name', 'id')->toArray()" />
    <x-filter-input name="is_published" label="Publication" type="select" :options="['' => 'All', '1' => 'Published', '0' => 'Draft']" />
</x-filter-panel>
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="responsive-table w-full text-sm"><thead class="bg-gray-50 border-b"><tr><th class="text-left px-6 py-3">Article</th><th class="text-left px-6 py-3">Category</th><th class="text-left px-6 py-3">Engagement</th><th class="text-left px-6 py-3">Status</th><th class="px-6 py-3"></th></tr></thead>
        <tbody class="divide-y">@forelse($articles as $article)<tr><td class="px-6 py-4"><p class="font-medium">{{ $article->title }}</p><p class="text-xs text-gray-500 truncate max-w-lg">{{ $article->excerpt ?: Str::limit(strip_tags($article->content), 100) }}</p></td><td class="px-6 py-4">{{ $article->category->name ?? '—' }}</td><td class="px-6 py-4 text-xs text-gray-500">{{ $article->view_count }} views · {{ $article->helpful_count }} helpful</td><td class="px-6 py-4"><x-status-badge :status="$article->is_published ? 'published' : 'draft'" />@if($article->is_featured)<span class="ml-1 text-yellow-500" title="Featured"><i class="fas fa-star"></i></span>@endif</td><td class="px-6 py-4 text-right"><a href="{{ route('admin.faq.articles.show', $article) }}" class="text-blue-600">View</a></td></tr>@empty<tr><td colspan="5"><x-empty-state icon="fa-circle-question" title="No FAQ articles found" description="Create your first FAQ article to help users find answers." /></td></tr>@endforelse</tbody>
    </table>
    @if($articles->hasPages())<div class="px-6 py-4 border-t">{{ $articles->links() }}</div>@endif
</div>
@endsection
