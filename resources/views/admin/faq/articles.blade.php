@extends('admin.layouts.app')
@section('title', 'FAQ Articles - LesGo Admin')
@section('header', 'FAQ Knowledge Base')

@section('actions')
<a href="{{ route('admin.faq.categories') }}" class="border border-gray-300 px-4 py-2 rounded-lg text-sm">Categories</a>
<a href="{{ route('admin.faq.articles.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-plus mr-1"></i> Add Article</a>
@endsection

@section('content')
<div class="bg-white rounded-xl shadow-sm p-4 mb-6"><form method="GET" class="flex gap-3 items-end flex-wrap"><div><label class="block text-xs text-gray-500 mb-1">Category</label><select name="category_id" class="border rounded-lg px-3 py-2 text-sm"><option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>@endforeach</select></div><div><label class="block text-xs text-gray-500 mb-1">Publication</label><select name="is_published" class="border rounded-lg px-3 py-2 text-sm"><option value="">All</option><option value="1" @selected(request('is_published') === '1')>Published</option><option value="0" @selected(request('is_published') === '0')>Draft</option></select></div><button class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm">Filter</button></form></div>
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="w-full text-sm"><thead class="bg-gray-50 border-b"><tr><th class="text-left px-6 py-3">Article</th><th class="text-left px-6 py-3">Category</th><th class="text-left px-6 py-3">Engagement</th><th class="text-left px-6 py-3">Status</th><th class="px-6 py-3"></th></tr></thead>
        <tbody class="divide-y">@forelse($articles as $article)<tr><td class="px-6 py-4"><p class="font-medium">{{ $article->title }}</p><p class="text-xs text-gray-500 truncate max-w-lg">{{ $article->excerpt ?: Str::limit(strip_tags($article->content), 100) }}</p></td><td class="px-6 py-4">{{ $article->category->name ?? '—' }}</td><td class="px-6 py-4 text-xs text-gray-500">{{ $article->view_count }} views · {{ $article->helpful_count }} helpful</td><td class="px-6 py-4"><span class="px-2 py-1 rounded-full text-xs {{ $article->is_published ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">{{ $article->is_published ? 'Published' : 'Draft' }}</span>@if($article->is_featured)<span class="ml-1 text-yellow-500" title="Featured"><i class="fas fa-star"></i></span>@endif</td><td class="px-6 py-4 text-right"><a href="{{ route('admin.faq.articles.show', $article) }}" class="text-blue-600">View</a></td></tr>@empty<tr><td colspan="5" class="px-6 py-10 text-center text-gray-400">No FAQ articles yet.</td></tr>@endforelse</tbody>
    </table>
    @if($articles->hasPages())<div class="px-6 py-4 border-t">{{ $articles->links() }}</div>@endif
</div>
@endsection
