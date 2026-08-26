@extends('admin.layouts.app')
@section('title', $article->title . ' - LesGo Admin')
@section('header', 'FAQ Article')

@section('actions')
<a href="{{ route('admin.faq.articles.edit', $article) }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm">Edit</a>
<button type="button" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm"
    x-data
    @click="$dispatch('confirm-modal', { title: 'Delete Article', message: 'Delete this article?', confirmText: 'Delete', onConfirm: () => { const f = document.createElement('form'); f.method = 'POST'; f.action = '{{ route('admin.faq.articles.destroy', $article) }}'; f.innerHTML = '<input type=\"hidden\" name=\"_token\" value=\"{{ csrf_token() }}\"><input type=\"hidden\" name=\"_method\" value=\"DELETE\">'; document.body.appendChild(f); f.submit(); } })">Delete</button>
@endsection

@section('content')
<div class="max-w-4xl space-y-6">
    <article class="bg-white rounded-xl shadow-sm p-8"><div class="flex gap-2 mb-4"><span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs">{{ $article->category->name ?? 'Uncategorized' }}</span><x-status-badge :status="$article->is_published ? 'published' : 'draft'" /></div><h1 class="text-2xl font-bold mb-3">{{ $article->title }}</h1>@if($article->excerpt)<p class="text-gray-500 mb-6">{{ $article->excerpt }}</p>@endif<div class="text-gray-800 whitespace-pre-wrap leading-7">{{ $article->content }}</div></article>
    <div class="bg-white rounded-xl shadow-sm p-5 text-sm text-gray-500 flex flex-wrap gap-6"><span>{{ $article->view_count }} views</span><span>{{ $article->helpful_count }} helpful</span><span>Created by {{ $article->creator->name ?? 'Unknown' }}</span><span>Updated {{ $article->updated_at->format('M d, Y H:i') }}</span></div>
</div>
@endsection
