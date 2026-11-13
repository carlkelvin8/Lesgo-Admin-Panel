@extends('admin.layouts.app')
@section('title', 'Business Records - LesGo Admin')
@section('header', 'Business Records')
@section('content')
<p class="mb-6 text-sm text-gray-500">Browse operational records with search, filters, record details, and CSV exports.</p>
@forelse($datasets as $group => $items)
<section class="mb-8"><h3 class="mb-3 font-semibold text-gray-900">{{ $group }}</h3>
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
@foreach($items as $key => $item)
<a href="{{ route('admin.data.index', $key) }}" class="rounded-xl border border-purple-100 bg-white p-5 shadow-sm hover:border-purple-400"><span class="font-semibold text-purple-800">{{ $item['title'] }}</span><span class="mt-2 block text-sm text-gray-500">Browse records <span aria-hidden="true">→</span></span></a>
@endforeach
</div></section>
@empty
<p class="rounded-xl bg-white p-6 text-gray-500">No additional record views are available for your role.</p>
@endforelse
@endsection
